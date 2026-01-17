import { NextResponse } from 'next/server';
import { getDb } from 'coze-coding-dev-sdk';

export async function GET() {
  try {
    const db = await getDb();
    
    return NextResponse.json({
      success: true,
      dbType: typeof db,
      dbMethods: Object.keys(Object.getPrototypeOf(db)).filter(k => typeof (db as any)[k] === 'function'),
      dbOwnProperties: Object.keys(db)
    });
  } catch (error: any) {
    return NextResponse.json({
      success: false,
      error: error.message
    }, { status: 500 });
  }
}
