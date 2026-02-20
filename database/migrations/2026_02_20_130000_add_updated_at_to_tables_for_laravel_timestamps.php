<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'tenants',
            'users',
            'projects',
            'tasks',
            'shifts',
            'notifications',
            'fcm_devices',
            'password_reset_tokens',
            'clock_sessions',
            'personal_todos',
            'reminders',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'updated_at')) {
                continue;
            }
            Schema::table($table, function (Blueprint $table) {
                $table->dateTime('updated_at')->nullable()->useCurrent()->after('created_at');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'tenants',
            'users',
            'projects',
            'tasks',
            'shifts',
            'notifications',
            'fcm_devices',
            'password_reset_tokens',
            'clock_sessions',
            'personal_todos',
            'reminders',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'updated_at')) {
                continue;
            }
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('updated_at');
            });
        }
    }
};
