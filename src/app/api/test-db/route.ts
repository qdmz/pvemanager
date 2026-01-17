import { NextResponse } from 'next/server';
import { getDb } from '@/db';
import { users } from '@/db/schema';

export async function GET() {
  try {
    console.log('Testing database connection...');
    const db = await getDb();
    const allUsers = await db.select().from(users).limit(1);
    console.log('Database query result:', allUsers);
    return NextResponse.json({
      success: true,
      userCount: allUsers.length,
      users: allUsers.map(u => ({ id: u.id, username: u.username, email: u.email }))
    });
  } catch (error: any) {
    console.error('Database test error:', error);
    return NextResponse.json({
      success: false,
      error: error.message,
      stack: error.stack
    }, { status: 500 });
  }
}
