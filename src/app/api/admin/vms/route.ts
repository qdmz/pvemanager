import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { virtualMachines, users, pveServers, vmOperations } from '@/db/schema';
import { verifyToken } from '@/lib/auth';
import { getPVEClient } from '@/lib/pve-client';
import { desc, eq, like, sql } from 'drizzle-orm';

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

    const db = await getDb();
    const searchParams = request.nextUrl.searchParams;
    const page = parseInt(searchParams.get('page') || '1');
    const pageSize = parseInt(searchParams.get('pageSize') || '20');
    const search = searchParams.get('search') || '';
    const userId = searchParams.get('userId');
    const serverId = searchParams.get('serverId');
    const status = searchParams.get('status');

    // Build query conditions
    const conditions = [];

    if (search) {
      conditions.push(like(virtualMachines.name, `%${search}%`));
    }

    if (userId) {
      conditions.push(eq(virtualMachines.userId, parseInt(userId)));
    }

    if (serverId) {
      conditions.push(eq(virtualMachines.serverId, parseInt(serverId)));
    }

    if (status) {
      conditions.push(eq(virtualMachines.status, status));
    }

    // Get total count
    const [{ count }] = await db
      .select({ count: sql<number>`count(*)::int` })
      .from(virtualMachines)
      .where(conditions.length > 0 ? sql`${conditions.reduce((acc, cond) => sql`${acc} AND ${cond}`)}` : undefined);

    // Get virtual machines with pagination
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
        autoShutdownOnExpiry: virtualMachines.autoShutdownOnExpiry,
        createdAt: virtualMachines.createdAt,
        node: virtualMachines.node,
        userId: virtualMachines.userId,
        userName: users.username,
        userEmail: users.email,
        serverId: virtualMachines.serverId,
        serverName: pveServers.name,
        serverHost: pveServers.host,
      })
      .from(virtualMachines)
      .leftJoin(users, eq(virtualMachines.userId, users.id))
      .leftJoin(pveServers, eq(virtualMachines.serverId, pveServers.id))
      .where(conditions.length > 0 ? sql`${conditions.reduce((acc, cond) => sql`${acc} AND ${cond}`)}` : undefined)
      .orderBy(desc(virtualMachines.createdAt))
      .limit(pageSize)
      .offset((page - 1) * pageSize);

    return NextResponse.json({
      success: true,
      vms,
      pagination: {
        page,
        pageSize,
        total: count,
        totalPages: Math.ceil(count / pageSize),
      },
    });
  } catch (error) {
    console.error('Get all VMs error:', error);
    return NextResponse.json({ error: '获取虚拟机列表失败' }, { status: 500 });
  }
}

export async function POST(request: NextRequest) {
  try {
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
      serverId,
      type,
      name,
      userId,
      templateId,
      cpuCores = 1,
      memory = 512,
      diskSize = 20,
      ipAddress,
      gateway,
      dnsServer,
      expiresAt,
      autoShutdownOnExpiry = true,
      rootPassword,
    } = body;

    if (!serverId || !type || !name || !userId || !templateId) {
      return NextResponse.json(
        { error: '缺少必要参数：serverId, type, name, userId, templateId' },
        { status: 400 }
      );
    }

    const db = await getDb();

    // Verify user exists
    const [user] = await db.select().from(users).where(eq(users.id, userId));
    if (!user) {
      return NextResponse.json({ error: '用户不存在' }, { status: 404 });
    }

    // Get PVE client
    const pveClient = await getPVEClient(serverId);

    // Get nodes and select the first one (or you can let admin specify)
    const nodesResponse = await pveClient.getNodes();
    const node = nodesResponse.data?.[0]?.node || 'pve';

    // Generate new VM ID (find next available ID)
    let newVmId = 100;
    const existingVMs = await db.select().from(virtualMachines);
    if (existingVMs.length > 0) {
      newVmId = Math.max(...existingVMs.map(vm => vm.vmId)) + 1;
    }

    try {
      let result;

      if (type === 'vm') {
        // Clone VM from template
        result = await pveClient.cloneVM(node, templateId, newVmId, name);

        // Update VM configuration
        await pveClient.updateVMConfig(node, newVmId, {
          cores: cpuCores,
          memory: memory,
          sockets: 1,
        });

        // Update VM status
        await db.insert(virtualMachines).values({
          vmId: newVmId,
          serverId,
          type,
          name,
          userId,
          status: 'stopped',
          cpuCores,
          memory,
          diskSize,
          ipAddress,
          gateway,
          dnsServer,
          expiresAt: expiresAt ? new Date(expiresAt) : null,
          autoShutdownOnExpiry,
          rootPassword,
          node,
        });
      } else if (type === 'ct') {
        // Clone LXC container from template
        result = await pveClient.cloneCT(node, templateId, newVmId, name);

        // Update CT configuration
        await pveClient.updateCTConfig(node, newVmId, {
          cores: cpuCores,
          memory: memory,
          swap: memory, // Same as memory
        });

        // Resize disk if needed
        if (diskSize > 0) {
          await pveClient.resizeCTDisk(node, newVmId, `${diskSize}G`);
        }

        // Update CT status
        await db.insert(virtualMachines).values({
          vmId: newVmId,
          serverId,
          type,
          name,
          userId,
          status: 'stopped',
          cpuCores,
          memory,
          diskSize,
          ipAddress,
          gateway,
          dnsServer,
          expiresAt: expiresAt ? new Date(expiresAt) : null,
          autoShutdownOnExpiry,
          rootPassword,
          node,
        });
      }

      // Log operation
      await db.insert(vmOperations).values({
        vmId: newVmId,
        operation: 'create',
        status: 'success',
        userId: decoded.userId,
      });

      return NextResponse.json({
        success: true,
        message: '虚拟机创建成功',
        vmId: newVmId,
        result,
      });
    } catch (error) {
      console.error('Create VM error:', error);

      // Log failed operation
      await db.insert(vmOperations).values({
        vmId: newVmId,
        operation: 'create',
        status: 'failed',
        message: error instanceof Error ? error.message : '创建失败',
        userId: decoded.userId,
      });

      throw error;
    }
  } catch (error) {
    console.error('Create VM error:', error);
    return NextResponse.json(
      { error: error instanceof Error ? error.message : '创建虚拟机失败' },
      { status: 500 }
    );
  }
}
