<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    protected array $defaults = [
        ['key' => 'day_start_hour', 'value' => '8', 'label' => 'Work Day Start Hour', 'description' => 'The hour the work day begins (24h format).', 'setting_type' => 'number'],
        ['key' => 'day_end_hour', 'value' => '16', 'label' => 'Work Day End Hour', 'description' => 'The hour the work day ends (24h format).', 'setting_type' => 'number'],
        ['key' => 'day_hours', 'value' => '8', 'label' => 'Work Day Length (hours)', 'description' => 'Total working hours in a standard day.', 'setting_type' => 'number'],
        ['key' => 'company_name', 'value' => 'Our Team', 'label' => 'Company / Team Name', 'description' => 'Displayed in the team schedule header.', 'setting_type' => 'string'],
        ['key' => 'project_description', 'value' => 'Project', 'label' => 'Project Label', 'description' => 'Custom label for "Project" shown in menus, dashboards, and forms (e.g. Client, Engagement). Singular form; plural is auto-derived.', 'setting_type' => 'string'],
        ['key' => 'week_start', 'value' => 'monday', 'label' => 'Week Starts On', 'description' => 'First day of the working week.', 'setting_type' => 'select:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
    ];

    public function run(): void
    {
        $tenantIds = DB::table('tenants')->pluck('id');
        foreach ($tenantIds as $tenantId) {
            $this->seedDefaultsForTenant($tenantId);
        }
    }

    public function seedDefaultsForTenant(int $tenantId): void
    {
        $existingKeys = DB::table('system_settings')
            ->where('tenant_id', $tenantId)
            ->pluck('key')
            ->flip();

        foreach ($this->defaults as $row) {
            if (! $existingKeys->has($row['key'])) {
                DB::table('system_settings')->insert([
                    'tenant_id' => $tenantId,
                    'key' => $row['key'],
                    'value' => $row['value'],
                    'label' => $row['label'],
                    'description' => $row['description'],
                    'setting_type' => $row['setting_type'],
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
