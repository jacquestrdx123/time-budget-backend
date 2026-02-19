<?php

namespace App\Http\Controllers;

use App\Models\FcmDevice;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->tenant_id;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->where('tenant_id', $this->tenantId($request));
        if ($request->query('unread_only') === 'true') {
            $query->where('is_read', false);
        }
        $notifications = $query->orderByDesc('created_at')->get();

        return response()->json($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('tenant_id', $this->tenantId($request))
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function preferencesMe(Request $request, NotificationService $notificationService): JsonResponse
    {
        $prefs = $notificationService->getOrCreatePreferences((int) $request->user()->id);

        return response()->json($prefs);
    }

    public function updatePreferencesMe(Request $request, NotificationService $notificationService): JsonResponse
    {
        $prefs = $notificationService->getOrCreatePreferences((int) $request->user()->id);

        $keys = ['push_enabled', 'email_enabled', 'task_assigned', 'task_updated', 'shift_reminder', 'project_updated', 'custom_reminder'];
        $updates = [];
        foreach ($keys as $key) {
            if ($request->has($key)) {
                $updates[$key] = $request->input($key);
            }
        }
        if (! empty($updates)) {
            $prefs->update($updates);
        }
        $prefs->refresh();

        return response()->json($prefs);
    }

    public function devicesMe(Request $request): JsonResponse
    {
        $devices = FcmDevice::where('user_id', $request->user()->id)->get();

        return response()->json($devices);
    }

    public function storeDevice(Request $request): JsonResponse
    {
        $token = $request->input('token');
        if (! $token) {
            return response()->json(['detail' => 'token is required'], 422);
        }

        $userId = $request->user()->id;
        $deviceName = $request->input('device_name');

        $existing = FcmDevice::where('token', $token)->first();
        if ($existing) {
            if ($existing->user_id != $userId || $existing->device_name !== $deviceName) {
                $existing->update(['user_id' => $userId, 'device_name' => $deviceName]);
                $existing->refresh();
            }

            return response()->json($existing);
        }

        try {
            $device = FcmDevice::create([
                'user_id' => $userId,
                'token' => $token,
                'device_name' => $deviceName,
            ]);

            return response()->json($device);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'UNIQUE')) {
                $conflict = FcmDevice::where('token', $token)->first();
                if ($conflict) {
                    $conflict->update(['user_id' => $userId, 'device_name' => $deviceName]);
                    $conflict->refresh();

                    return response()->json($conflict);
                }
            }
            throw $e;
        }
    }

    public function destroyDevice(Request $request, string $deviceId): JsonResponse
    {
        $device = FcmDevice::where('id', $deviceId)->where('user_id', $request->user()->id)->first();
        if (! $device) {
            return response()->json(['detail' => 'Device not found'], 404);
        }
        $device->delete();

        return response()->json(null, 204);
    }

    public function testPush(Request $request, NotificationService $notificationService): JsonResponse
    {
        $result = $notificationService->testPushToUser((int) $request->user()->id);
        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    public function send(Request $request, NotificationService $notificationService): JsonResponse
    {
        $userId = $request->input('user_id');
        $title = $request->input('title');
        $body = $request->input('body');
        if (! $userId || ! $title || ! $body) {
            return response()->json(['detail' => 'user_id, title, and body are required'], 422);
        }

        $tenantId = $this->tenantId($request);
        $targetUser = User::where('id', $userId)->where('tenant_id', $tenantId)->first();
        if (! $targetUser) {
            return response()->json(['detail' => 'Target user not found'], 404);
        }

        $notification = $notificationService->sendNotification([
            'userId' => (int) $userId,
            'tenantId' => $targetUser->tenant_id,
            'title' => $title,
            'body' => $body,
            'notificationType' => $request->input('notification_type', 'general'),
            'data' => $request->input('data'),
        ]);

        return response()->json($notification);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->where('tenant_id', $this->tenantId($request))
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['detail' => 'All notifications marked as read']);
    }

    public function show(Request $request, string $notificationId): JsonResponse
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $request->user()->id)
            ->where('tenant_id', $this->tenantId($request))
            ->first();
        if (! $notification) {
            return response()->json(['detail' => 'Notification not found'], 404);
        }

        return response()->json($notification);
    }

    public function markRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $request->user()->id)
            ->where('tenant_id', $this->tenantId($request))
            ->first();
        if (! $notification) {
            return response()->json(['detail' => 'Notification not found'], 404);
        }
        $notification->update(['is_read' => true]);
        $notification->refresh();

        return response()->json($notification);
    }

    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $request->user()->id)
            ->where('tenant_id', $this->tenantId($request))
            ->first();
        if (! $notification) {
            return response()->json(['detail' => 'Notification not found'], 404);
        }
        $notification->delete();

        return response()->json(null, 204);
    }
}
