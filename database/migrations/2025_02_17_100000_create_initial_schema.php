<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable()->default('My Team');
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('password_hash', 255);
            $table->string('role', 64)->default('member');
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['tenant_id', 'email']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('name', 255);
            $table->string('description', 1024)->nullable();
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('title', 255);
            $table->string('description', 1024)->nullable();
            $table->string('status', 64)->default('pending');
            $table->foreignId('project_id')->constrained('projects');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->boolean('is_break')->default(false);
            $table->string('break_type', 255)->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('project_id')->references('id')->on('projects');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('user_id')->constrained('users');
            $table->string('title', 255);
            $table->text('body');
            $table->string('notification_type', 64)->default('general');
            $table->string('channel', 32)->default('all');
            $table->boolean('is_read')->default(false);
            $table->boolean('sent_push')->default(false);
            $table->boolean('sent_email')->default(false);
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->boolean('push_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('task_assigned')->default(true);
            $table->boolean('task_updated')->default(true);
            $table->boolean('shift_reminder')->default(true);
            $table->boolean('project_updated')->default(true);
        });

        Schema::create('fcm_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('token', 512)->unique();
            $table->string('device_name', 255)->nullable();
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('token', 64)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('key', 255);
            $table->text('value');
            $table->string('label', 255);
            $table->string('description', 1024)->nullable();
            $table->string('setting_type', 64)->default('string');
            $table->dateTime('updated_at')->useCurrent();
            $table->primary(['tenant_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('fcm_devices');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
    }
};
