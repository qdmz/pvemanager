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
    const { operation } = body;

    if (!operation) {
      return NextResponse.json({ error: '缺少操作类型' }, { status: 400 });
    }

    const db = await getDb();
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
        operation,
        status: 'pending',
        userId: decoded.userId,
      })
      .returning();

    try {
      const pveClient = await getPVEClient(vm.serverId);
      let result;

      switch (operation) {
        case 'start':
          if (vm.type === 'vm') {
            result = await pveClient.startVM(vm.node, vm.vmId);
          } else {
            result = await pveClient.startCT(vm.node, vm.vmId);
          }
          // Update status
          await db
            .update(virtualMachines)
            .set({ status: 'running' })
            .where(eq(virtualMachines.id, vm.id));
          break;

        case 'stop':
          if (vm.type === 'vm') {
            result = await pveClient.stopVM(vm.node, vm.vmId);
          } else {
            result = await pveClient.stopCT(vm.node, vm.vmId);
          }
          await db
            .update(virtualMachines)
            .set({ status: 'stopped' })
            .where(eq(virtualMachines.id, vm.id));
          break;

        case 'restart':
          if (vm.type === 'vm') {
            result = await pveClient.restartVM(vm.node, vm.vmId);
          } else {
            result = await pveClient.restartCT(vm.node, vm.vmId);
          }
          break;

        case 'shutdown':
          if (vm.type === 'vm') {
            result = await pveClient.shutdownVM(vm.node, vm.vmId);
          } else {
            result = await pveClient.shutdownCT(vm.node, vm.vmId);
          }
          break;

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
        message: `${operation} 操作成功`,
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
    console.error('VM operation error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '操作失败' },
      { status: 500 }
    );
  }
}
