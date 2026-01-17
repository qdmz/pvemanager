import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { virtualMachines, vmOperations } from '@/db/schema';
import { verifyToken } from '@/lib/auth';
import { getPVEClient } from '@/lib/pve-client';
import { eq, and } from 'drizzle-orm';

export async function POST(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const { id } = await params;
    const token = request.cookies.get('auth-token')?.value;

    if (!token) {
      return NextResponse.json({ error: '未登录' }, { status: 401 });
    }

    const decoded = verifyToken(token);
    if (!decoded) {
      return NextResponse.json({ error: 'Token 无效' }, { status: 401 });
    }

    const body = await request.json();
    const { action, data } = body;

    if (!action) {
      return NextResponse.json({ error: '缺少操作类型' }, { status: 400 });
    }

    const db = getDb();
    const vmId = parseInt(id);

    // Get VM details
    const [vm] = await db
      .select()
      .from(virtualMachines)
      .where(
        and(
          eq(virtualMachines.id, vmId),
          decoded.role === 'admin' ? undefined : eq(virtualMachines.userId, decoded.userId)
        )
      );

    if (!vm) {
      return NextResponse.json({ error: '虚拟机不存在' }, { status: 404 });
    }

    // Check if VM is expired
    if (vm.expiresAt && new Date(vm.expiresAt) < new Date()) {
      return NextResponse.json(
        { error: '虚拟机已到期，无法操作' },
        { status: 403 }
      );
    }

    // Log operation
    const [opRecord] = await db
      .insert(vmOperations)
      .values({
        vmId: vm.id,
        operation: action,
        status: 'pending',
        userId: decoded.userId,
      })
      .returning();

    try {
      const pveClient = await getPVEClient(vm.serverId);
      let result;

      switch (action) {
        case 'reset_password': {
          if (!data?.newPassword) {
            return NextResponse.json({ error: '缺少新密码' }, { status: 400 });
          }

          if (vm.type === 'ct') {
            // For LXC containers
            result = await pveClient.resetCTPassword(vm.node, vm.vmId, data.newPassword);

            // Update root password in database
            await db
              .update(virtualMachines)
              .set({ rootPassword: data.newPassword })
              .where(eq(virtualMachines.id, vm.id));
          } else {
            // For VMs, we need to use QEMU guest agent or modify the config
            // For now, return unsupported for VMs
            throw new Error('虚拟机类型不支持重置密码');
          }

          break;
        }

        case 'reinstall': {
          if (!data?.template) {
            return NextResponse.json({ error: '缺少模板信息' }, { status: 400 });
          }

          if (vm.type === 'ct') {
            // Reinstall LXC container
            await pveClient.updateCTConfig(vm.node, vm.vmId, {
              template: data.template,
            });

            // Stop and start container
            await pveClient.stopCT(vm.node, vm.vmId);
            await pveClient.startCT(vm.node, vm.vmId);

            result = { message: '重装成功' };
          } else {
            // For VMs, use ISO or template
            // This is more complex, for now return unsupported
            throw new Error('虚拟机重装功能需要指定 ISO 镜像');
          }

          break;
        }

        case 'get_ssh_info': {
          if (vm.type === 'ct') {
            // Get LXC container network config
            const config = await pveClient.getCTConfig(vm.node, vm.vmId);

            // Parse network config
            const networkInfo = {
              ipAddress: config.lxcNetwork?.ip || vm.ipAddress,
              gateway: vm.gateway,
              dnsServer: vm.dnsServer,
              port: 22, // Default SSH port
              username: 'root',
              password: vm.rootPassword || '请联系管理员获取密码',
              node: vm.node,
              vmId: vm.vmId,
            };

            result = networkInfo;
          } else {
            // Get VM network config
            const config = await pveClient.getVMConfig(vm.node, vm.vmId);

            const networkInfo = {
              ipAddress: config.ipconfig0?.ip || vm.ipAddress,
              gateway: vm.gateway,
              dnsServer: vm.dnsServer,
              port: 22,
              username: 'root',
              password: vm.rootPassword || '请联系管理员获取密码',
              node: vm.node,
              vmId: vm.vmId,
            };

            result = networkInfo;
          }

          break;
        }

        case 'get_console': {
          if (vm.type === 'vm') {
            result = await pveClient.getVMConsole(vm.node, vm.vmId);
          } else {
            result = await pveClient.getCTConsole(vm.node, vm.vmId);
          }

          // Extract VNC ticket from response
          if (result.data) {
            result = {
              type: vm.type,
              node: vm.node,
              vmId: vm.vmId,
              ticket: result.data.ticket,
              port: result.data.port,
              host: result.data.host || (await pveClient.getNodes()).data?.[0]?.node,
            };
          }

          break;
        }

        case 'get_termproxy': {
          if (vm.type !== 'ct') {
            return NextResponse.json({ error: '仅支持 LXC 容器' }, { status: 400 });
          }

          result = await pveClient.getTermProxy(vm.node, vm.vmId);

          if (result.data) {
            result = {
              type: 'termproxy',
              node: vm.node,
              vmId: vm.vmId,
              ticket: result.data.ticket,
              port: result.data.port,
              host: result.data.host || (await pveClient.getNodes()).data?.[0]?.node,
            };
          }

          break;
        }

        default:
          return NextResponse.json({ error: '不支持的操作' }, { status: 400 });
      }

      // Update operation status
      await db
        .update(vmOperations)
        .set({ status: 'success' })
        .where(eq(vmOperations.id, opRecord.id));

      return NextResponse.json({
        success: true,
        message: `${action} 操作成功`,
        result,
      });
    } catch (error) {
      // Update operation status to failed
      await db
        .update(vmOperations)
        .set({
          status: 'failed',
          message: error instanceof Error ? error.message : '未知错误'
        })
        .where(eq(vmOperations.id, opRecord.id));

      throw error;
    }
  } catch (error) {
    console.error('VM advanced operation error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '操作失败' },
      { status: 500 }
    );
  }
}

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const { id } = await params;
    const token = request.cookies.get('auth-token')?.value;

    if (!token) {
      return NextResponse.json({ error: '未登录' }, { status: 401 });
    }

    const decoded = verifyToken(token);
    if (!decoded) {
      return NextResponse.json({ error: 'Token 无效' }, { status: 401 });
    }

    const db = getDb();
    const vmId = parseInt(id);

    // Get VM details
    const [vm] = await db
      .select()
      .from(virtualMachines)
      .where(
        and(
          eq(virtualMachines.id, vmId),
          decoded.role === 'admin' ? undefined : eq(virtualMachines.userId, decoded.userId)
        )
      );

    if (!vm) {
      return NextResponse.json({ error: '虚拟机不存在' }, { status: 404 });
    }

    const pveClient = await getPVEClient(vm.serverId);

    // Get current status from PVE
    let statusData;
    if (vm.type === 'vm') {
      statusData = await pveClient.getVMStatus(vm.node, vm.vmId);
    } else {
      statusData = await pveClient.getCTStatus(vm.node, vm.vmId);
    }

    // Get config
    let configData;
    if (vm.type === 'vm') {
      configData = await pveClient.getVMConfig(vm.node, vm.vmId);
    } else {
      configData = await pveClient.getCTConfig(vm.node, vm.vmId);
    }

    // Get operations history
    const operations = await db
      .select()
      .from(vmOperations)
      .where(eq(vmOperations.vmId, vm.id))
      .limit(10);

    return NextResponse.json({
      success: true,
      vm: {
        ...vm,
        status: statusData?.data?.status || vm.status,
        uptime: statusData?.data?.uptime,
        cpu: statusData?.data?.cpu,
        memory: statusData?.data?.mem,
        disk: statusData?.data?.disk,
        config: configData?.data,
      },
      operations: operations.reverse(),
    });
  } catch (error) {
    console.error('Get VM details error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '获取虚拟机详情失败' },
      { status: 500 }
    );
  }
}
