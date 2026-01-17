import { NextResponse } from 'next/server';
import pg from 'pg';

const { Pool } = pg;

export async function GET() {
  try {
    console.log('Testing pg connection...');
    console.log('DATABASE_URL:', process.env.DATABASE_URL ? 'SET' : 'NOT_SET');
    
    const pool = new Pool({
      connectionString: process.env.DATABASE_URL,
      ssl: false,
    });
    
    console.log('Pool created');
    
    const result = await pool.query('SELECT COUNT(*) FROM users');
    console.log('Query result:', result.rows[0]);
    
    await pool.end();
    
    return NextResponse.json({
      success: true,
      count: result.rows[0].count,
    });
  } catch (error: any) {
    console.error('pg test error:', error);
    return NextResponse.json({
      success: false,
      error: error.message,
      stack: error.stack
    }, { status: 500 });
  }
}
