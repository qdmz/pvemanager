import { NextRequest, NextResponse } from 'next/server';
import { getDb } from '@/db';
import { users } from '@/db/schema';
import { hashPassword } from '@/lib/auth';
import { or, eq } from 'drizzle-orm';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { username, email, password } = body;

    console.log('Register request:', { username, email });

    if (!username || !email || !password) {
      return NextResponse.json(
        { error: '缺少必要字段' },
        { status: 400 }
      );
    }

    if (password.length < 6) {
      return NextResponse.json(
        { error: '密码长度至少为6位' },
        { status: 400 }
      );
    }

    console.log('Getting database connection...');
    const db = await getDb();

    console.log('Checking if user exists...');
    // Check if user already exists
    const existingUser = await db.select().from(users).where(
      or(eq(users.username, username), eq(users.email, email))
    );

    if (existingUser.length > 0) {
      return NextResponse.json(
        { error: '用户名或邮箱已存在' },
        { status: 409 }
      );
    }

    console.log('Hashing password...');
    // Create new user
    const hashedPassword = await hashPassword(password);

    console.log('Inserting user...');
    const [newUser] = await db.insert(users).values({
      username,
      email,
      password: hashedPassword,
      role: 'user',
    }).returning();

    console.log('User created:', newUser);

    // Return user without password
    const { password: _, ...userWithoutPassword } = newUser;

    return NextResponse.json({
      success: true,
      user: userWithoutPassword
    }, { status: 201 });
  } catch (error) {
    console.error('Registration error:', error);
    return NextResponse.json(
      { error: '注册失败，请稍后重试' },
      { status: 500 }
    );
  }
}
