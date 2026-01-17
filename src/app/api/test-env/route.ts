import { NextResponse } from 'next/server';

export async function GET() {
  return NextResponse.json({
    DATABASE_URL_SET: !!process.env.DATABASE_URL,
    DATABASE_URL_LENGTH: process.env.DATABASE_URL?.length || 0,
    DATABASE_URL_PREFIX: process.env.DATABASE_URL?.substring(0, 20) || 'NONE',
    NODE_ENV: process.env.NODE_ENV,
  });
}
