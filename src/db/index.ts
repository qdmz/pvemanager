import { getDb as getSdkDb } from 'coze-coding-dev-sdk';

let db: Awaited<ReturnType<typeof getSdkDb>> | null = null;

export async function getDb() {
  if (!db) {
    console.log('Creating database connection via SDK...');
    try {
      const dbResult = await getSdkDb();
      console.log('Database connection created:', typeof dbResult);

      // Validate db instance
      if (!dbResult || typeof dbResult !== 'object') {
        throw new Error('Invalid database instance returned from SDK');
      }

      db = dbResult;
    } catch (error) {
      console.error('Failed to create database connection:', error);
      throw new Error(`Database connection failed: ${error instanceof Error ? error.message : String(error)}`);
    }
  }
  return db;
}
