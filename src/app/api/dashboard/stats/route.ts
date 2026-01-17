import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { virtualMachines, vmOperations } from '@/db/schema';
import { verifyToken } from '@/lib/auth';
import { eq, and, sql, desc, lt, gte, sum } from 'drizzle-orm';

export async function GET(request: NextRequest) {
  try {
    const token = request.cookies.get('auth-token')?.value;

    if (!token) {
      return NextResponse.json({ error: '未登录' }, { status: 401 });
    }

    const decoded = verifyToken(token);
    if (!decoded) {
      return NextResponse.json({ error: 'Token 无效' }, { status: 401 });
    }

    const db = getDb();
    const userId = decoded.userId;

    // VM 统计
    const vmCounts = await db
      .select({
        status: virtualMachines.status,
        count: sql<number>`count(*)::int`,
      })
      .from(virtualMachines)
      .where(eq(virtualMachines.userId, userId))
      .groupBy(virtualMachines.status);

    const totalVMs = vmCounts.reduce((sum, item) => sum + item.count, 0);
    const runningVMs = vmCounts.find(x => x.status === 'running')?.count || 0;

    // 资源使用统计
    const resourceStats = await db
      .select({
        totalCpu: sum(virtualMachines.cpuCores),
        totalMemory: sum(virtualMachines.memory),
        totalDisk: sum(virtualMachines.diskSize),
      })
      .from(virtualMachines)
      .where(eq(virtualMachines.userId, userId));

    // 即将到期的虚拟机（7天内）
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 7);
    const expiringVMs = await db
      .select()
      .from(virtualMachines)
      .where(
        and(
          eq(virtualMachines.userId, userId),
          lt(virtualMachines.expiresAt, futureDate),
          gte(virtualMachines.expiresAt, new Date())
        )
      );

    // 最近操作
    const recentOperations = await db
      .select({
        id: vmOperations.id,
        operation: vmOperations.operation,
        status: vmOperations.status,
        vmId: vmOperations.vmId,
        createdAt: vmOperations.createdAt,
      })
      .from(vmOperations)
      .where(eq(vmOperations.userId, userId))
      .orderBy(desc(vmOperations.createdAt))
      .limit(10);

    // 虚拟机列表
    const vmList = await db
      .select()
      .from(virtualMachines)
      .where(eq(virtualMachines.userId, userId))
      .orderBy(desc(virtualMachines.createdAt));

    return NextResponse.json({
      success: true,
      stats: {
        vm: {
          total: totalVMs,
          running: runningVMs,
          stopped: vmCounts.find(x => x.status === 'stopped')?.count || 0,
          paused: vmCounts.find(x => x.status === 'paused')?.count || 0,
          expiring: expiringVMs.length,
        },
        resource: {
          cpu: resourceStats[0]?.totalCpu || 0,
          memory: resourceStats[0]?.totalMemory || 0,
          memoryGB: ((resourceStats[0]?.totalMemory || 0) / 1024).toFixed(2),
          disk: resourceStats[0]?.totalDisk || 0,
        },
      },
      recentOperations,
      vms: vmList,
    });
  } catch (error) {
    console.error('Get user stats error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '获取用户统计数据失败' },
      { status: 500 }
    );
  }
}
