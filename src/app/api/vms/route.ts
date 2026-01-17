import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { virtualMachines, users, pveServers } from '@/db/schema';
import { verifyToken } from '@/lib/auth';
import { eq, and } from 'drizzle-orm';

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

    const db = await getDb();

    // Get user's virtual machines
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
        serverName: pveServers.name,
        node: virtualMachines.node,
      })
      .from(virtualMachines)
      .leftJoin(pveServers, eq(virtualMachines.serverId, pveServers.id))
      .where(eq(virtualMachines.userId, decoded.userId))
      .orderBy(virtualMachines.createdAt);

    return NextResponse.json({ success: true, vms });
  } catch (error) {
    console.error('Get VMs error:', error);
    return NextResponse.json({ error: '获取虚拟机列表失败' }, { status: 500 });
  }
}
