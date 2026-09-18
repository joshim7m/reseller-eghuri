<?php

namespace App\Http\Controllers\StoreFront;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->take(10)
            ->get();

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function page(Request $request): Response
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->where('created_at', '>=', Carbon::now()->subWeek())
            ->latest()
            ->paginate(20);

        return Inertia::render('StoreFront/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
