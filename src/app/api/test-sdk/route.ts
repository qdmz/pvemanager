import { NextResponse } from 'next/server';

export async function GET() {
  try {
    console.log('Trying to use coze-coding-dev-sdk...');
    
    // Try to import getDb from coze-coding-dev-sdk
    let getDb;
    try {
      const sdk = await import('coze-coding-dev-sdk');
      getDb = sdk.getDb;
      console.log('getDb function imported from SDK');
    } catch (err) {
      console.error('Failed to import coze-coding-dev-sdk:', err);
      return NextResponse.json({
        success: false,
        error: 'SDK not available',
        message: (err as Error).message
      }, { status: 500 });
    }
    
    const db = await getDb();
    console.log('Database connection established via SDK');
    
    // Try a simple query
    const result = await db.execute('SELECT 1 as test');
    console.log('Query result:', result);
    
    return NextResponse.json({
      success: true,
      message: 'SDK database connection successful',
      result
    });
  } catch (error: any) {
    console.error('SDK test error:', error);
    return NextResponse.json({
      success: false,
      error: error.message,
      stack: error.stack
    }, { status: 500 });
  }
}
