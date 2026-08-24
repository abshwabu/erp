<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Notifications\Models\InAppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $query = InAppNotification::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(25);
        $unreadCount = InAppNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data'         => $notifications->items(),
            'current_page' => $notifications->currentPage(),
            'last_page'    => $notifications->lastPage(),
            'total'        => $notifications->total(),
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = InAppNotification::query()
            ->where('user_id', $request->user()?->id)
            ->findOrFail($id);

        $notification->markAsRead();

        return $this->successResponse($notification);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        InAppNotification::query()
            ->where('user_id', $request->user()?->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->successResponse(['message' => 'All notifications marked as read']);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'    => ['required', 'uuid', 'exists:users,id'],
            'type'       => ['sometimes', 'in:info,success,warning,alert'],
            'title'      => ['required', 'string', 'max:255'],
            'message'    => ['required', 'string'],
            'action_url' => ['nullable', 'string', 'max:255'],
            'data'       => ['nullable', 'array'],
        ]);

        $notification = InAppNotification::create([
            'user_id'    => $data['user_id'],
            'type'       => $data['type'] ?? 'info',
            'title'      => $data['title'],
            'message'    => $data['message'],
            'action_url' => $data['action_url'] ?? null,
            'data'       => $data['data'] ?? null,
        ]);

        return $this->createdResponse($notification);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = InAppNotification::query()
            ->where('user_id', $request->user()?->id)
            ->findOrFail($id);

        $notification->delete();

        return $this->noContentResponse();
    }
}
