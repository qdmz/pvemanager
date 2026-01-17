import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { virtualMachines, pveServers } from '@/db/schema';
import { verifyToken } from '@/lib/auth';
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

    // Get virtual machine details
    const [vm] = await db
      .select({
        id: virtualMachines.id,
        vmId: virtualMachines.vmId,
        type: virtualMachines.type,
        name: virtualMachines.name,
        status: virtualMachines.status,
        cpuCores: virtualMachines.cpuCores,
        memory: virtualMachines.memory,
        diskSize: virtualMachines.diskSize,
        template: virtualMachines.template,
        ipAddress: virtualMachines.ipAddress,
        gateway: virtualMachines.gateway,
        dnsServer: virtualMachines.dnsServer,
        natEnabled: virtualMachines.natEnabled,
        natPortForward: virtualMachines.natPortForward,
        expiresAt: virtualMachines.expiresAt,
        autoShutdownOnExpiry: virtualMachines.autoShutdownOnExpiry,
        createdAt: virtualMachines.createdAt,
        updatedAt: virtualMachines.updatedAt,
        node: virtualMachines.node,
        serverId: virtualMachines.serverId,
        serverName: pveServers.name,
        serverHost: pveServers.host,
        serverPort: pveServers.port,
      })
      .from(virtualMachines)
      .leftJoin(pveServers, eq(virtualMachines.serverId, pveServers.id))
      .where(
        and(
          eq(virtualMachines.id, parseInt(id)),
          decoded.role === 'admin' ? undefined : eq(virtualMachines.userId, decoded.userId)
        )
      );

    if (!vm) {
      return NextResponse.json({ error: '虚拟机不存在' }, { status: 404 });
    }

    return NextResponse.json({ success: true, vm });
  } catch (error) {
    console.error('Get VM error:', error);
    return NextResponse.json({ error: '获取虚拟机详情失败' }, { status: 500 });
  }
}
