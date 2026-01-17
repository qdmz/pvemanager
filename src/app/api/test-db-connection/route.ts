import { getDb } from '@/db';
import { users } from '@/db/schema';

async function testDb() {
  try {
    console.log('Testing database connection...');
    const db = await getDb();
    console.log('DB instance:', typeof db);

    console.log('Testing simple query...');
    const result = await db.select().from(users).limit(1);
    console.log('Query result:', result);

    console.log('Database test successful!');
    return { success: true, result };
  } catch (error) {
    console.error('Database test failed:', error);
    return { success: false, error: String(error) };
  }
}

export async function GET() {
  const result = await testDb();
  return Response.json(result);
}
