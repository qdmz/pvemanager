import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { virtualMachines, vmOperations } from '@/db/schema';
import { verifyToken } from '@/lib/auth';
import { getPVEClient } from '@/lib/pve-client';
import { eq } from 'drizzle-orm';

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
    if (!decoded || decoded.role !== 'admin') {
      return NextResponse.json({ error: '权限不足' }, { status: 403 });
    }

    const body = await request.json();
    const { resource, config } = body;

    if (!resource) {
      return NextResponse.json({ error: '缺少资源配置类型' }, { status: 400 });
    }

    const db = await getDb();
    const vmId = parseInt(id);

    // Get VM details
    const [vm] = await db
      .select()
      .from(virtualMachines)
      .where(eq(virtualMachines.id, vmId));

    if (!vm) {
      return NextResponse.json({ error: '虚拟机不存在' }, { status: 404 });
    }

    // Log operation
    const [opRecord] = await db
      .insert(vmOperations)
      .values({
        vmId: vm.id,
        operation: `reconfigure_${resource}`,
        status: 'pending',
        userId: decoded.userId,
      })
      .returning();

    try {
      const pveClient = await getPVEClient(vm.serverId);
      let result;

      switch (resource) {
        case 'cpu': {
          const { cores, sockets = 1, cpuType = 'host' } = config;

          if (vm.type === 'vm') {
            await pveClient.updateVMConfig(vm.node, vm.vmId, {
              cores: cores,
              sockets: sockets,
              cpu: cpuType,
            });
          } else {
            await pveClient.updateCTConfig(vm.node, vm.vmId, {
              cores: cores,
            });
          }

          // Update database
          await db
            .update(virtualMachines)
            .set({ cpuCores: cores })
            .where(eq(virtualMachines.id, vmId));

          result = { message: 'CPU 配置已更新' };
          break;
        }

        case 'memory': {
          const { memory, swap } = config;

          if (vm.type === 'vm') {
            await pveClient.updateVMConfig(vm.node, vm.vmId, {
              memory: memory,
              balloon: 0,
            });
          } else {
            await pveClient.updateCTConfig(vm.node, vm.vmId, {
              memory: memory,
              swap: swap || memory,
            });
          }

          // Update database
          await db
            .update(virtualMachines)
            .set({ memory: memory })
            .where(eq(virtualMachines.id, vmId));

          result = { message: '内存配置已更新' };
          break;
        }

        case 'disk': {
          const { disk = 'scsi0', size, storage } = config;

          if (storage) {
            // Move disk to another storage
            if (vm.type === 'vm') {
              await pveClient.moveDisk(vm.node, vm.vmId, disk, storage);
            }
          } else if (size) {
            // Resize disk
            if (vm.type === 'vm') {
              await pveClient.resizeDisk(vm.node, vm.vmId, disk, `${size}G`);
            } else {
              await pveClient.resizeCTDisk(vm.node, vm.vmId, `${size}G`);
            }

            // Update database
            await db
              .update(virtualMachines)
              .set({ diskSize: parseInt(size) })
              .where(eq(virtualMachines.id, vmId));
          }

          result = { message: '磁盘配置已更新' };
          break;
        }

        case 'network': {
          const { bridge, model = 'virtio', tag } = config;

          if (vm.type === 'vm') {
            const net0 = `${model}=bridge=${bridge}${tag ? `,tag=${tag}` : ''}`;
            await pveClient.updateVMConfig(vm.node, vm.vmId, {
              net0: net0,
            });
          } else {
            const lxcNetwork = {
              type: 'veth',
              bridge: bridge,
              name: 'eth0',
              tag: tag,
            };
            await pveClient.updateCTConfig(vm.node, vm.vmId, {
              net0: JSON.stringify(lxcNetwork),
            });
          }

          result = { message: '网络配置已更新' };
          break;
        }

        case 'ip': {
          const { ipAddress, gateway, dnsServer, natEnabled, natPortForward } = config;

          // Update database
          const updateData: any = {};
          if (ipAddress !== undefined) updateData.ipAddress = ipAddress;
          if (gateway !== undefined) updateData.gateway = gateway;
          if (dnsServer !== undefined) updateData.dnsServer = dnsServer;
          if (natEnabled !== undefined) updateData.natEnabled = natEnabled;
          if (natPortForward !== undefined) updateData.natPortForward = natPortForward;

          await db
            .update(virtualMachines)
            .set(updateData)
            .where(eq(virtualMachines.id, vmId));

          result = { message: 'IP 配置已更新' };
          break;
        }

        default:
          return NextResponse.json({ error: '不支持的资源配置类型' }, { status: 400 });
      }

      // Update operation status
      await db
        .update(vmOperations)
        .set({ status: 'success' })
        .where(eq(vmOperations.id, opRecord.id));

      return NextResponse.json({
        success: true,
        message: `${resource} 配置更新成功`,
        result,
      });
    } catch (error) {
      // Update operation status to failed
      await db
        .update(vmOperations)
        .set({
          status: 'failed',
          message: error instanceof Error ? error.message : '配置更新失败'
        })
        .where(eq(vmOperations.id, opRecord.id));

      throw error;
    }
  } catch (error) {
    console.error('Reconfigure VM error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '配置更新失败' },
      { status: 500 }
    );
  }
}
