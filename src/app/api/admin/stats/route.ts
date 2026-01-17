import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { virtualMachines, users, pveServers, vmOperations } from '@/db/schema';
import { verifyToken } from '@/lib/auth';
import { eq, and, sql, desc, lt, gte, count, sum } from 'drizzle-orm';

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
    const isAdmin = decoded.role === 'admin';

    // 基础统计
    let vmStats = {};
    let userStats = {};
    let serverStats = {};
    let recentOperations = [];
    let resourceUsage = {};

    if (isAdmin) {
      // 管理员可以看到全局统计

      // VM 统计
      const vmCounts = await db
        .select({
          status: virtualMachines.status,
          count: sql<number>`count(*)::int`,
        })
        .from(virtualMachines)
        .groupBy(virtualMachines.status);

      const totalVMs = vmCounts.reduce((sum, item) => sum + item.count, 0);
      const runningVMs = vmCounts.find(x => x.status === 'running')?.count || 0;

      // 用户统计
      const userCountResult = await db
        .select({ count: sql<number>`count(*)::int` })
        .from(users);

      // PVE 服务器统计
      const serverCountResult = await db
        .select({ count: sql<number>`count(*)::int` })
        .from(pveServers);

      // 资源使用统计
      const resourceStats = await db
        .select({
          totalCpu: sum(virtualMachines.cpuCores),
          totalMemory: sum(virtualMachines.memory),
          totalDisk: sum(virtualMachines.diskSize),
        })
        .from(virtualMachines);

      // 即将到期的虚拟机（7天内）
      const futureDate = new Date();
      futureDate.setDate(futureDate.getDate() + 7);
      const expiringVMs = await db
        .select()
        .from(virtualMachines)
        .where(
          and(
            lt(virtualMachines.expiresAt, futureDate),
            gte(virtualMachines.expiresAt, new Date())
          )
        );

      // 最近操作
      recentOperations = await db
        .select({
          id: vmOperations.id,
          operation: vmOperations.operation,
          status: vmOperations.status,
          vmId: vmOperations.vmId,
          userId: vmOperations.userId,
          createdAt: vmOperations.createdAt,
        })
        .from(vmOperations)
        .orderBy(desc(vmOperations.createdAt))
        .limit(10);

      vmStats = {
        total: totalVMs,
        running: runningVMs,
        stopped: vmCounts.find(x => x.status === 'stopped')?.count || 0,
        paused: vmCounts.find(x => x.status === 'paused')?.count || 0,
        expiring: expiringVMs.length,
      };

      userStats = {
        total: userCountResult[0]?.count || 0,
        active: userCountResult[0]?.count || 0, // TODO: 统计活跃用户（最近30天登录）
      };

      serverStats = {
        total: serverCountResult[0]?.count || 0,
        active: serverCountResult[0]?.count || 0, // TODO: 检查服务器连接状态
      };

      resourceUsage = {
        cpu: resourceStats[0]?.totalCpu || 0,
        memory: resourceStats[0]?.totalMemory || 0,
        memoryGB: ((resourceStats[0]?.totalMemory || 0) / 1024).toFixed(2),
        disk: resourceStats[0]?.totalDisk || 0,
      };
    } else {
      // 普通用户只能看到自己的统计

      // VM 统计
      const vmCounts = await db
        .select({
          status: virtualMachines.status,
          count: sql<number>`count(*)::int`,
        })
        .from(virtualMachines)
        .where(eq(virtualMachines.userId, decoded.userId))
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
        .where(eq(virtualMachines.userId, decoded.userId));

      // 即将到期的虚拟机
      const futureDate = new Date();
      futureDate.setDate(futureDate.getDate() + 7);
      const expiringVMs = await db
        .select()
        .from(virtualMachines)
        .where(
          and(
            eq(virtualMachines.userId, decoded.userId),
            lt(virtualMachines.expiresAt, futureDate),
            gte(virtualMachines.expiresAt, new Date())
          )
        );

      // 最近操作
      recentOperations = await db
        .select({
          id: vmOperations.id,
          operation: vmOperations.operation,
          status: vmOperations.status,
          vmId: vmOperations.vmId,
          createdAt: vmOperations.createdAt,
        })
        .from(vmOperations)
        .where(eq(vmOperations.userId, decoded.userId))
        .orderBy(desc(vmOperations.createdAt))
        .limit(10);

      vmStats = {
        total: totalVMs,
        running: runningVMs,
        stopped: vmCounts.find(x => x.status === 'stopped')?.count || 0,
        paused: vmCounts.find(x => x.status === 'paused')?.count || 0,
        expiring: expiringVMs.length,
      };

      resourceUsage = {
        cpu: resourceStats[0]?.totalCpu || 0,
        memory: resourceStats[0]?.totalMemory || 0,
        memoryGB: ((resourceStats[0]?.totalMemory || 0) / 1024).toFixed(2),
        disk: resourceStats[0]?.totalDisk || 0,
      };
    }

    return NextResponse.json({
      success: true,
      stats: {
        vm: vmStats,
        user: userStats,
        server: serverStats,
        resource: resourceUsage,
      },
      recentOperations,
    });
  } catch (error) {
    console.error('Get stats error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '获取统计数据失败' },
      { status: 500 }
    );
  }
}
