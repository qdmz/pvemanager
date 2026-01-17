import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { virtualMachines, vmOperations } from '@/db/schema';
import { verifyToken } from '@/lib/auth';
import { getPVEClient } from '@/lib/pve-client';
import { eq, and } from 'drizzle-orm';

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

    // Check permissions
    if (decoded.role !== 'admin' && vm.userId !== decoded.userId) {
      return NextResponse.json({ error: '权限不足' }, { status: 403 });
    }

    return NextResponse.json({ success: true, vm });
  } catch (error) {
    console.error('Get VM error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '获取虚拟机失败' },
      { status: 500 }
    );
  }
}

export async function PUT(
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
    const {
      name,
      userId,
      ipAddress,
      gateway,
      dnsServer,
      expiresAt,
      autoShutdownOnExpiry,
      rootPassword,
    } = body;

    const db = await getDb();
    const vmId = parseInt(id);

    // Check if VM exists
    const [existingVM] = await db
      .select()
      .from(virtualMachines)
      .where(eq(virtualMachines.id, vmId));

    if (!existingVM) {
      return NextResponse.json({ error: '虚拟机不存在' }, { status: 404 });
    }

    // Update VM in database
    const updateData: any = {};

    if (name !== undefined) updateData.name = name;
    if (userId !== undefined) updateData.userId = userId;
    if (ipAddress !== undefined) updateData.ipAddress = ipAddress;
    if (gateway !== undefined) updateData.gateway = gateway;
    if (dnsServer !== undefined) updateData.dnsServer = dnsServer;
    if (expiresAt !== undefined) updateData.expiresAt = expiresAt ? new Date(expiresAt) : null;
    if (autoShutdownOnExpiry !== undefined) updateData.autoShutdownOnExpiry = autoShutdownOnExpiry;
    if (rootPassword !== undefined) updateData.rootPassword = rootPassword;

    await db
      .update(virtualMachines)
      .set(updateData)
      .where(eq(virtualMachines.id, vmId));

    return NextResponse.json({
      success: true,
      message: '虚拟机更新成功',
    });
  } catch (error) {
    console.error('Update VM error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '更新虚拟机失败' },
      { status: 500 }
    );
  }
}

export async function DELETE(
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

    try {
      // Stop VM if running
      if (vm.status === 'running') {
        const pveClient = await getPVEClient(vm.serverId);

        if (vm.type === 'vm') {
          await pveClient.stopVM(vm.node, vm.vmId);
        } else {
          await pveClient.stopCT(vm.node, vm.vmId);
        }
      }

      // Delete VM from PVE
      const pveClient = await getPVEClient(vm.serverId);

      if (vm.type === 'vm') {
        await pveClient.deleteVM(vm.node, vm.vmId);
      } else {
        await pveClient.deleteCT(vm.node, vm.vmId);
      }

      // Delete VM from database
      await db.delete(virtualMachines).where(eq(virtualMachines.id, vmId));

      // Log operation
      await db.insert(vmOperations).values({
        vmId: vmId,
        operation: 'delete',
        status: 'success',
        userId: decoded.userId,
      });

      return NextResponse.json({
        success: true,
        message: '虚拟机删除成功',
      });
    } catch (error) {
      // Log failed operation
      await db.insert(vmOperations).values({
        vmId: vmId,
        operation: 'delete',
        status: 'failed',
        message: error instanceof Error ? error.message : '删除失败',
        userId: decoded.userId,
      });

      throw error;
    }
  } catch (error) {
    console.error('Delete VM error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '删除虚拟机失败' },
      { status: 500 }
    );
  }
}
