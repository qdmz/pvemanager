import pg from 'pg';

const { Pool } = pg;

async function testConnection() {
  console.log('DATABASE_URL:', process.env.DATABASE_URL);

  const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
  });

  try {
    const result = await pool.query('SELECT COUNT(*) FROM users');
    console.log('Success:', result.rows[0]);
  } catch (error: any) {
    console.error('Error:', error.message);
  } finally {
    await pool.end();
  }
}

testConnection();
