<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::table('tenants')->select('id')->get();
        foreach ($tenants as $tenant) {
            $exists = DB::table('system_settings')
                ->where('tenant_id', $tenant->id)
                ->where('key', 'project_description')
                ->exists();
            if (! $exists) {
                DB::table('system_settings')->insert([
                    'tenant_id' => $tenant->id,
                    'key' => 'project_description',
                    'value' => 'Project',
                    'label' => 'Project Label',
                    'description' => 'Custom label for "Project" shown in menus, dashboards, and forms (e.g. Client, Engagement). Singular form; plural is auto-derived.',
                    'setting_type' => 'string',
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Optional: leave data in place for data integrity
    }
};
