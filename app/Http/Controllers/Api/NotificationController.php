<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit');
        $type = $request->input('type');
        $query = Notification::where('user_id', $request->user()->id)->orderBy('created_at', 'desc');
        if ($type) {
    $query->where('type', $type);
}

if ($limit) {
    $query->limit($limit);
}

$notifications = $query->get();
        return response()->json(['message' => 'List of notifications', 'payload' => $notifications], 200);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }
        $notification->read_at = now();
        $notification->save();
        return response()->json(['message' => 'Notification marked as read'], 200);
    }
    
}
