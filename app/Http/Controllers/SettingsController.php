<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->tenant_id;
    }

    public function index(Request $request): JsonResponse
    {
        $settings = DB::table('system_settings')
            ->where('tenant_id', $this->tenantId($request))
            ->orderBy('key')
            ->get();

        return response()->json($settings);
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $setting = DB::table('system_settings')
            ->where('tenant_id', $this->tenantId($request))
            ->where('key', $key)
            ->first();
        if (! $setting) {
            return response()->json(['detail' => 'Setting not found'], 404);
        }

        return response()->json($setting);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $setting = DB::table('system_settings')
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();
        if (! $setting) {
            return response()->json(['detail' => 'Setting not found'], 404);
        }

        if (! $request->has('value')) {
            return response()->json(['detail' => 'value is required'], 422);
        }
        $value = $request->input('value');

        DB::table('system_settings')
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->update(['value' => (string) $value, 'updated_at' => now()]);

        $updated = DB::table('system_settings')
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        return response()->json($updated);
    }

    public function store(Request $request): JsonResponse
    {
        $key = $request->input('key');
        $value = $request->input('value');
        $label = $request->input('label');
        if (! $key || ! $value || ! $label) {
            return response()->json(['detail' => 'key, value, and label are required'], 422);
        }

        $tenantId = $this->tenantId($request);
        $existing = DB::table('system_settings')
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();
        if ($existing) {
            return response()->json(['detail' => 'Setting already exists'], 409);
        }

        DB::table('system_settings')->insert([
            'tenant_id' => $tenantId,
            'key' => $key,
            'value' => $value,
            'label' => $label,
            'description' => $request->input('description'),
            'setting_type' => $request->input('setting_type', 'string'),
            'updated_at' => now(),
        ]);

        $setting = DB::table('system_settings')
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        return response()->json($setting, 201);
    }

    public function destroy(Request $request, string $key): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $setting = DB::table('system_settings')
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();
        if (! $setting) {
            return response()->json(['detail' => 'Setting not found'], 404);
        }

        DB::table('system_settings')
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->delete();

        return response()->json(null, 204);
    }
}
