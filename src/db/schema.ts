import { pgTable, serial, text, timestamp, boolean, integer, jsonb } from 'drizzle-orm/pg-core';

export const users = pgTable('users', {
  id: serial('id').primaryKey(),
  username: text('username').notNull().unique(),
  email: text('email').notNull().unique(),
  password: text('password').notNull(),
  role: text('role').notNull().default('user'), // 'user' or 'admin'
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
});

export const pveServers = pgTable('pve_servers', {
  id: serial('id').primaryKey(),
  name: text('name').notNull(),
  host: text('host').notNull(),
  port: integer('port').notNull().default(8006),
  username: text('username').notNull(),
  apiToken: text('api_token').notNull(),
  realm: text('realm').notNull().default('pam'),
  isActive: boolean('is_active').notNull().default(true),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
});

export const virtualMachines = pgTable('virtual_machines', {
  id: serial('id').primaryKey(),
  vmId: integer('vm_id').notNull(), // PVE VM ID
  serverId: integer('server_id').notNull().references(() => pveServers.id),
  type: text('type').notNull(), // 'vm' or 'ct'
  name: text('name').notNull(),
  userId: integer('user_id').notNull().references(() => users.id),
  status: text('status').notNull().default('stopped'), // 'running', 'stopped', 'paused'
  
  // Hardware configuration
  cpuCores: integer('cpu_cores').notNull().default(1),
  memory: integer('memory').notNull(), // in MB
  diskSize: integer('disk_size').notNull(), // in GB
  
  // System configuration
  template: text('template'),
  rootPassword: text('root_password'),
  
  // Network configuration
  ipAddress: text('ip_address'),
  gateway: text('gateway'),
  dnsServer: text('dns_server'),
  natEnabled: boolean('nat_enabled').notNull().default(false),
  natPortForward: jsonb('nat_port_forward').$type<{ hostPort: number; vmPort: number; protocol: 'tcp' | 'udp' }[]>(),
  
  // Lifecycle
  expiresAt: timestamp('expires_at'),
  autoShutdownOnExpiry: boolean('auto_shutdown_on_expiry').notNull().default(true),
  
  // PVE specific
  node: text('node').notNull(), // PVE node name
  
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
});

export const vmOperations = pgTable('vm_operations', {
  id: serial('id').primaryKey(),
  vmId: integer('vm_id').notNull().references(() => virtualMachines.id),
  operation: text('operation').notNull(), // 'start', 'stop', 'restart', 'shutdown', 'reinstall', 'reset_password', 'reconfigure'
  status: text('status').notNull().default('pending'), // 'pending', 'success', 'failed'
  message: text('message'),
  userId: integer('user_id').notNull().references(() => users.id),
  createdAt: timestamp('created_at').notNull().defaultNow(),
});

export const systemSettings = pgTable('system_settings', {
  id: serial('id').primaryKey(),
  key: text('key').notNull().unique(),
  value: text('value').notNull(),
  description: text('description'),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
});
