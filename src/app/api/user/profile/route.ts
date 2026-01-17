import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { users } from '@/db/schema';
import { verifyToken, hashPassword, verifyPassword } from '@/lib/auth';
import { eq } from 'drizzle-orm';

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
    const [user] = await db.select({
      id: users.id,
      username: users.username,
      email: users.email,
      role: users.role,
      createdAt: users.createdAt,
      updatedAt: users.updatedAt,
    }).from(users).where(eq(users.id, decoded.userId));

    if (!user) {
      return NextResponse.json({ error: '用户不存在' }, { status: 404 });
    }

    return NextResponse.json({ success: true, user });
  } catch (error) {
    console.error('Get profile error:', error);
    return NextResponse.json({ error: '获取用户信息失败' }, { status: 500 });
  }
}

export async function PUT(request: NextRequest) {
  try {
    const token = request.cookies.get('auth-token')?.value;

    if (!token) {
      return NextResponse.json({ error: '未登录' }, { status: 401 });
    }

    const decoded = verifyToken(token);
    if (!decoded) {
      return NextResponse.json({ error: 'Token 无效' }, { status: 401 });
    }

    const body = await request.json();
    const { email, currentPassword, newPassword } = body;

    if (email) {
      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        return NextResponse.json({ error: '邮箱格式不正确' }, { status: 400 });
      }
    }

    const db = getDb();
    const [user] = await db.select().from(users).where(eq(users.id, decoded.userId));

    if (!user) {
      return NextResponse.json({ error: '用户不存在' }, { status: 404 });
    }

    const updates: any = {};

    // Update email
    if (email && email !== user.email) {
      // Check if email is already taken
      const [existingUser] = await db.select().from(users).where(eq(users.email, email));
      if (existingUser && existingUser.id !== user.id) {
        return NextResponse.json({ error: '邮箱已被使用' }, { status: 409 });
      }
      updates.email = email;
    }

    // Update password
    if (newPassword) {
      if (!currentPassword) {
        return NextResponse.json({ error: '请输入当前密码' }, { status: 400 });
      }

      const isValidPassword = await verifyPassword(currentPassword, user.password);
      if (!isValidPassword) {
        return NextResponse.json({ error: '当前密码错误' }, { status: 401 });
      }

      if (newPassword.length < 6) {
        return NextResponse.json({ error: '新密码长度至少为6位' }, { status: 400 });
      }

      updates.password = await hashPassword(newPassword);
    }

    if (Object.keys(updates).length === 0) {
      return NextResponse.json({ error: '没有需要更新的内容' }, { status: 400 });
    }

    updates.updatedAt = new Date();

    const [updatedUser] = await db
      .update(users)
      .set(updates)
      .where(eq(users.id, decoded.userId))
      .returning({
        id: users.id,
        username: users.username,
        email: users.email,
        role: users.role,
        createdAt: users.createdAt,
        updatedAt: users.updatedAt,
      });

    return NextResponse.json({ success: true, user: updatedUser });
  } catch (error) {
    console.error('Update profile error:', error);
    return NextResponse.json({ error: '更新用户信息失败' }, { status: 500 });
  }
}
