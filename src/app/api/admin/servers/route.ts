import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { pveServers } from '@/db/schema';
import { verifyToken } from '@/lib/auth';

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
    const servers = await db.select().from(pveServers).orderBy(pveServers.createdAt);

    return NextResponse.json({ success: true, servers });
  } catch (error) {
    console.error('Get servers error:', error);
    return NextResponse.json({ error: '获取服务器列表失败' }, { status: 500 });
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
    const { name, host, port, username, apiToken, realm } = body;

    if (!name || !host || !username || !apiToken) {
      return NextResponse.json({ error: '缺少必要字段' }, { status: 400 });
    }

    const db = await getDb();
    const [newServer] = await db.insert(pveServers).values({
      name,
      host,
      port: port || 8006,
      username,
      apiToken,
      realm: realm || 'pam',
      isActive: true,
    }).returning();

    return NextResponse.json({ success: true, server: newServer }, { status: 201 });
  } catch (error) {
    console.error('Create server error:', error);
    return NextResponse.json({ error: '创建服务器失败' }, { status: 500 });
  }
}
