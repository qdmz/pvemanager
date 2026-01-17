import { NextResponse } from 'next/server';

export async function GET() {
  return NextResponse.json({
    DATABASE_URL: process.env.DATABASE_URL ? 'SET' : 'NOT_SET',
    NODE_ENV: process.env.NODE_ENV,
  });
}
