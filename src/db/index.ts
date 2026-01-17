import { getDb as getSdkDb } from 'coze-coding-dev-sdk';

let db: ReturnType<typeof getSdkDb> | null = null;

export function getDb() {
  if (!db) {
    console.log('Creating database connection via SDK...');
    db = getSdkDb();
    console.log('Database connection created');
  }
  return db;
}
