import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { virtualMachines, users, pveServers } from '@/db/schema';
import { verifyToken } from '@/lib/auth';
import { desc, eq } from 'drizzle-orm';

export async function GET(request: NextRequest) {
  try {
    const token = request.cookies.get('auth-token')?.value;

    if (!token) {
      return NextResponse.json({ error: '未登录' }, { status: 401 });
    }

    const decoded = verifyToken(token);
    if (!decoded || decoded.role !== 'admin') {
      return NextResponse.json({ error: '权限不足' }, { status: 403 });
    }

    const db = getDb();

    // Get all virtual machines with user and server info
    const vms = await db
      .select({
        id: virtualMachines.id,
        vmId: virtualMachines.vmId,
        type: virtualMachines.type,
        name: virtualMachines.name,
        status: virtualMachines.status,
        cpuCores: virtualMachines.cpuCores,
        memory: virtualMachines.memory,
        diskSize: virtualMachines.diskSize,
        ipAddress: virtualMachines.ipAddress,
        expiresAt: virtualMachines.expiresAt,
        createdAt: virtualMachines.createdAt,
        node: virtualMachines.node,
        userId: virtualMachines.userId,
        userName: users.username,
        serverName: pveServers.name,
      })
      .from(virtualMachines)
      .leftJoin(users, eq(virtualMachines.userId, users.id))
      .leftJoin(pveServers, eq(virtualMachines.serverId, pveServers.id))
      .orderBy(desc(virtualMachines.createdAt));

    return NextResponse.json({ success: true, vms });
  } catch (error) {
    console.error('Get all VMs error:', error);
    return NextResponse.json({ error: '获取虚拟机列表失败' }, { status: 500 });
  }
}
