CREATE TABLE "pve_servers" (
	"id" serial PRIMARY KEY NOT NULL,
	"name" text NOT NULL,
	"host" text NOT NULL,
	"port" integer DEFAULT 8006 NOT NULL,
	"username" text NOT NULL,
	"api_token" text NOT NULL,
	"realm" text DEFAULT 'pam' NOT NULL,
	"is_active" boolean DEFAULT true NOT NULL,
	"created_at" timestamp DEFAULT now() NOT NULL,
	"updated_at" timestamp DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "system_settings" (
	"id" serial PRIMARY KEY NOT NULL,
	"key" text NOT NULL,
	"value" text NOT NULL,
	"description" text,
	"updated_at" timestamp DEFAULT now() NOT NULL,
	CONSTRAINT "system_settings_key_unique" UNIQUE("key")
);
--> statement-breakpoint
CREATE TABLE "users" (
	"id" serial PRIMARY KEY NOT NULL,
	"username" text NOT NULL,
	"email" text NOT NULL,
	"password" text NOT NULL,
	"role" text DEFAULT 'user' NOT NULL,
	"created_at" timestamp DEFAULT now() NOT NULL,
	"updated_at" timestamp DEFAULT now() NOT NULL,
	CONSTRAINT "users_username_unique" UNIQUE("username"),
	CONSTRAINT "users_email_unique" UNIQUE("email")
);
--> statement-breakpoint
CREATE TABLE "virtual_machines" (
	"id" serial PRIMARY KEY NOT NULL,
	"vm_id" integer NOT NULL,
	"server_id" integer NOT NULL,
	"type" text NOT NULL,
	"name" text NOT NULL,
	"user_id" integer NOT NULL,
	"status" text DEFAULT 'stopped' NOT NULL,
	"cpu_cores" integer DEFAULT 1 NOT NULL,
	"memory" integer NOT NULL,
	"disk_size" integer NOT NULL,
	"template" text,
	"root_password" text,
	"ip_address" text,
	"gateway" text,
	"dns_server" text,
	"nat_enabled" boolean DEFAULT false NOT NULL,
	"nat_port_forward" jsonb,
	"expires_at" timestamp,
	"auto_shutdown_on_expiry" boolean DEFAULT true NOT NULL,
	"node" text NOT NULL,
	"created_at" timestamp DEFAULT now() NOT NULL,
	"updated_at" timestamp DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "vm_operations" (
	"id" serial PRIMARY KEY NOT NULL,
	"vm_id" integer NOT NULL,
	"operation" text NOT NULL,
	"status" text DEFAULT 'pending' NOT NULL,
	"message" text,
	"user_id" integer NOT NULL,
	"created_at" timestamp DEFAULT now() NOT NULL
);
--> statement-breakpoint
ALTER TABLE "virtual_machines" ADD CONSTRAINT "virtual_machines_server_id_pve_servers_id_fk" FOREIGN KEY ("server_id") REFERENCES "public"."pve_servers"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "virtual_machines" ADD CONSTRAINT "virtual_machines_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "vm_operations" ADD CONSTRAINT "vm_operations_vm_id_virtual_machines_id_fk" FOREIGN KEY ("vm_id") REFERENCES "public"."virtual_machines"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "vm_operations" ADD CONSTRAINT "vm_operations_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;