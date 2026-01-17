import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { virtualMachines, vmOperations } from '@/db/schema';
import { getPVEClient } from '@/lib/pve-client';
import { eq, lt, and } from 'drizzle-orm';

/**
 * 检查并处理到期的虚拟机
 * 这个接口应该由定时任务定期调用（例如使用 cron）
 */
export async function POST(request: NextRequest) {
  try {
    const token = request.headers.get('authorization');

    // 简单的认证检查（生产环境应该使用更安全的认证）
    if (token !== `Bearer ${process.env.CRON_SECRET || 'secret'}`) {
      return NextResponse.json({ error: '未授权' }, { status: 401 });
    }

    const db = await getDb();
    const now = new Date();

    // 查找所有已到期且设置了自动关机的虚拟机
    const expiredVMs = await db
      .select()
      .from(virtualMachines)
      .where(
        and(
          lt(virtualMachines.expiresAt, now),
          eq(virtualMachines.autoShutdownOnExpiry, true),
          eq(virtualMachines.status, 'running')
        )
      );

    const results = [];

    for (const vm of expiredVMs) {
      try {
        const pveClient = await getPVEClient(vm.serverId);

        // 停止虚拟机
        if (vm.type === 'vm') {
          await pveClient.shutdownVM(vm.node, vm.vmId);
        } else {
          await pveClient.shutdownCT(vm.node, vm.vmId);
        }

        // 更新数据库状态
        await db
          .update(virtualMachines)
          .set({ status: 'stopped' })
          .where(eq(virtualMachines.id, vm.id));

        // 记录操作
        await db.insert(vmOperations).values({
          vmId: vm.id,
          operation: 'auto_shutdown_expired',
          status: 'success',
          message: '虚拟机到期自动关机',
          userId: 0, // 系统操作
        });

        results.push({
          vmId: vm.id,
          name: vm.name,
          status: 'shutdown',
          message: '已自动关机',
        });
      } catch (error) {
        console.error(`Failed to shutdown expired VM ${vm.id}:`, error);

        await db.insert(vmOperations).values({
          vmId: vm.id,
          operation: 'auto_shutdown_expired',
          status: 'failed',
          message: error instanceof Error ? error.message : '自动关机失败',
          userId: 0,
        });

        results.push({
          vmId: vm.id,
          name: vm.name,
          status: 'failed',
          message: error instanceof Error ? error.message : '自动关机失败',
        });
      }
    }

    return NextResponse.json({
      success: true,
      message: `处理了 ${expiredVMs.length} 个到期的虚拟机`,
      results,
    });
  } catch (error) {
    console.error('Check expiry error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '检查到期失败' },
      { status: 500 }
    );
  }
}

/**
 * 获取即将到期的虚拟机列表（管理员用）
 */
export async function GET(request: NextRequest) {
  try {
    const token = request.cookies.get('auth-token')?.value;
    if (!token) {
      return NextResponse.json({ error: '未登录' }, { status: 401 });
    }

    const { verifyToken } = await import('@/lib/auth');
    const decoded = verifyToken(token);

    if (!decoded || decoded.role !== 'admin') {
      return NextResponse.json({ error: '权限不足' }, { status: 403 });
    }

    const db = await getDb();
    const searchParams = request.nextUrl.searchParams;
    const days = parseInt(searchParams.get('days') || '7');

    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + days);

    // 查找在未来几天内到期的虚拟机
    const expiringVMs = await db
      .select()
      .from(virtualMachines)
      .where(lt(virtualMachines.expiresAt, futureDate))
      .orderBy(virtualMachines.expiresAt);

    return NextResponse.json({
      success: true,
      expiringVMs: expiringVMs.map(vm => ({
        ...vm,
        daysUntilExpiry: Math.ceil(
          (new Date(vm.expiresAt!).getTime() - Date.now()) / (1000 * 60 * 60 * 24)
        ),
      })),
    });
  } catch (error) {
    console.error('Get expiring VMs error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '获取到期虚拟机列表失败' },
      { status: 500 }
    );
  }
}
