import pg from 'pg';

const { Pool } = pg;

async function initDatabase() {
  const connectionString = process.env.DATABASE_URL;
  
  if (!connectionString) {
    console.error('DATABASE_URL environment variable is not set');
    process.exit(1);
  }

  console.log('Connecting to database...');
  const pool = new Pool({ connectionString });

  try {
    console.log('Creating tables...');
    
    // Create tables using SQL
    const client = await pool.connect();
    
    // Create users table
    await client.query(`
      CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user',
        created_at TIMESTAMP NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `);
    
    // Create pve_servers table
    await client.query(`
      CREATE TABLE IF NOT EXISTS pve_servers (
        id SERIAL PRIMARY KEY,
        name TEXT NOT NULL,
        host TEXT NOT NULL,
        port INTEGER NOT NULL DEFAULT 8006,
        username TEXT NOT NULL,
        api_token TEXT NOT NULL,
        realm TEXT NOT NULL DEFAULT 'pam',
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at TIMESTAMP NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `);
    
    // Create virtual_machines table
    await client.query(`
      CREATE TABLE IF NOT EXISTS virtual_machines (
        id SERIAL PRIMARY KEY,
        vm_id INTEGER NOT NULL,
        server_id INTEGER NOT NULL REFERENCES pve_servers(id),
        type TEXT NOT NULL,
        name TEXT NOT NULL,
        user_id INTEGER NOT NULL REFERENCES users(id),
        status TEXT NOT NULL DEFAULT 'stopped',
        cpu_cores INTEGER NOT NULL DEFAULT 1,
        memory INTEGER NOT NULL,
        disk_size INTEGER NOT NULL,
        template TEXT,
        root_password TEXT,
        ip_address TEXT,
        gateway TEXT,
        dns_server TEXT,
        nat_enabled BOOLEAN NOT NULL DEFAULT FALSE,
        nat_port_forward JSONB,
        expires_at TIMESTAMP,
        auto_shutdown_on_expiry BOOLEAN NOT NULL DEFAULT TRUE,
        node TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `);
    
    // Create vm_operations table
    await client.query(`
      CREATE TABLE IF NOT EXISTS vm_operations (
        id SERIAL PRIMARY KEY,
        vm_id INTEGER NOT NULL REFERENCES virtual_machines(id),
        operation TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'pending',
        message TEXT,
        user_id INTEGER NOT NULL REFERENCES users(id),
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `);
    
    // Create system_settings table
    await client.query(`
      CREATE TABLE IF NOT EXISTS system_settings (
        id SERIAL PRIMARY KEY,
        key TEXT NOT NULL UNIQUE,
        value TEXT NOT NULL,
        description TEXT,
        updated_at TIMESTAMP NOT NULL DEFAULT NOW()
      )
    `);
    
    client.release();
    
    console.log('✓ Database tables created successfully');
    console.log('\nTables created:');
    console.log('  - users');
    console.log('  - pve_servers');
    console.log('  - virtual_machines');
    console.log('  - vm_operations');
    console.log('  - system_settings');
    
  } catch (error) {
    console.error('Error initializing database:', error);
    process.exit(1);
  } finally {
    await pool.end();
  }
}

initDatabase();
