<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions grouped by module
        $permissions = [
            // User management
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.suspend',

            // KYC
            'kyc.submit', 'kyc.review', 'kyc.approve', 'kyc.reject',

            // SIP accounts
            'sip_accounts.view', 'sip_accounts.create', 'sip_accounts.update', 'sip_accounts.delete',

            // Trunks
            'trunks.view', 'trunks.create', 'trunks.update', 'trunks.delete',
            'trunks.health_monitor',

            // Trunk routes
            'trunk_routes.view', 'trunk_routes.create', 'trunk_routes.update', 'trunk_routes.delete',

            // DIDs
            'dids.view', 'dids.create', 'dids.update', 'dids.delete', 'dids.assign',

            // Rates
            'rates.view', 'rates.create', 'rates.update', 'rates.delete',
            'rates.import', 'rates.export',

            // Billing
            'billing.view', 'billing.recharge', 'billing.adjust',
            'invoices.view', 'invoices.create',
            'transactions.view',
            'payments.view', 'payments.create',

            // CDR
            'cdr.view', 'cdr.export',

            // Dashboard
            'dashboard.admin', 'dashboard.reseller', 'dashboard.client',

            // Transfers
            'transfers.execute', 'transfers.view',

            // Security
            'blacklist.view', 'blacklist.manage',
            'whitelist.view', 'whitelist.manage',
            'audit_logs.view',

            // System
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Super Admin — gets everything, manages global system features
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // Admin — manages assigned resellers and their clients (no global system access)
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            // User management (scoped to assigned resellers)
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.suspend',

            // KYC
            'kyc.review', 'kyc.approve', 'kyc.reject',

            // SIP accounts (scoped)
            'sip_accounts.view', 'sip_accounts.create', 'sip_accounts.update', 'sip_accounts.delete',

            // DIDs (scoped)
            'dids.view', 'dids.create', 'dids.update', 'dids.delete', 'dids.assign',

            // Billing (scoped)
            'billing.view', 'billing.recharge', 'billing.adjust',
            'invoices.view', 'invoices.create',
            'transactions.view',
            'payments.view', 'payments.create',

            // CDR (scoped)
            'cdr.view', 'cdr.export',

            // Dashboard
            'dashboard.admin',

            // Transfers (scoped)
            'transfers.execute', 'transfers.view',
        ]);

        // Reseller — manages own clients, SIP accounts, rates, billing
        $resellerRole = Role::create(['name' => 'reseller']);
        $resellerRole->givePermissionTo([
            'users.view', 'users.create', 'users.update', 'users.suspend',
            'kyc.submit', 'kyc.review', 'kyc.approve', 'kyc.reject',
            'sip_accounts.view', 'sip_accounts.create', 'sip_accounts.update', 'sip_accounts.delete',
            'dids.view', 'dids.assign',
            'rates.view', 'rates.create', 'rates.update', 'rates.import', 'rates.export',
            'billing.view', 'billing.recharge',
            'invoices.view',
            'transactions.view',
            'payments.view', 'payments.create',
            'cdr.view', 'cdr.export',
            'dashboard.reseller',
        ]);

        // Client — views own data, submits KYC, manages own SIP accounts
        $clientRole = Role::create(['name' => 'client']);
        $clientRole->givePermissionTo([
            'kyc.submit',
            'sip_accounts.view', 'sip_accounts.create', 'sip_accounts.update',
            'dids.view',
            'rates.view',
            'billing.view',
            'invoices.view',
            'transactions.view',
            'payments.view', 'payments.create',
            'cdr.view', 'cdr.export',
            'dashboard.client',
        ]);

        // Recharge Admin — view-only access + balance operations for assigned resellers
        $rechargeAdminRole = Role::create(['name' => 'recharge_admin']);
        $rechargeAdminRole->givePermissionTo([
            'users.view',
            'sip_accounts.view',
            'dids.view',
            'cdr.view',
            'transactions.view',
            'billing.view',
            'billing.recharge',  // Only balance operation allowed
        ]);
    }
}
