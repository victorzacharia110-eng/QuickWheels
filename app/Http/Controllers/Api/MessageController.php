<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $withUserId = $request->query('with_user');

        if ($withUserId) {
            $messages = Message::where(function ($q) use ($userId, $withUserId) {
                $q->where('sender_id', $userId)->where('receiver_id', $withUserId);
            })->orWhere(function ($q) use ($userId, $withUserId) {
                $q->where('sender_id', $withUserId)->where('receiver_id', $userId);
            })->with(['sender', 'receiver'])
              ->orderBy('created_at', 'asc')
              ->get();

            Message::where('sender_id', $withUserId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);

            return response()->json(['success' => true, 'data' => $messages->map(fn($m) => $m->toApiResponse())]);
        }

        $conversations = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver'])
            ->latest('created_at')
            ->get()
            ->groupBy(function ($msg) use ($userId) {
                return $msg->sender_id === $userId ? $msg->receiver_id : $msg->sender_id;
            })
            ->map(function ($msgs) {
                return [
                    'user' => $msgs->first()->sender_id === $msgs->first()->receiver_id
                        ? $msgs->first()->receiver
                        : $msgs->first()->sender,
                    'last_message' => $msgs->first()->toApiResponse(),
                    'unread_count' => $msgs->filter(fn($m) => !$m->is_read && $m->receiver_id === $msgs->first()->receiver_id)->count(),
                ];
            })
            ->values();

        return response()->json(['success' => true, 'data' => $conversations]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'body' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $sender = $request->user();
        $receiver = User::find($request->receiver_id);

        if ($sender->id === $request->receiver_id) {
            return response()->json(['success' => false, 'message' => 'Cannot send message to yourself'], 422);
        }

        $ownerId = $sender->owner?->id ?? $receiver->owner?->id;

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $request->receiver_id,
            'owner_id' => $ownerId,
            'body' => $request->body,
        ]);

        $message->load(['sender', 'receiver']);

        return response()->json([
            'success' => true,
            'data' => $message->toApiResponse(),
        ], 201);
    }

    public function unreadCount(Request $request)
    {
        $count = Message::where('receiver_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['success' => true, 'data' => ['count' => $count]]);
    }

    public function contacts(Request $request)
    {
        $user = $request->user();
        $role = $user->role;

        if ($role === 'owner') {
            $employees = Employee::where('owner_id', $user->owner?->id)
                ->whereHas('user')
                ->with('user')
                ->get()
                ->map(fn($e) => [
                    'id' => $e->user_id,
                    'name' => $e->user->name,
                    'role' => strtolower($e->position) === 'technician' ? 'technician' : 'employee',
                ]);

            return response()->json(['success' => true, 'data' => $employees]);
        }

        if ($role === 'technician' || $role === 'employee') {
            $employee = Employee::where('user_id', $user->id)->first();

            if (!$employee) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $ownerId = $employee->owner_id;
            $contacts = collect();

            $owner = User::where('role', 'owner')
                ->whereHas('owner', fn($q) => $q->id = $ownerId)
                ->first();
            if ($owner) {
                $contacts->push(['id' => $owner->id, 'name' => $owner->name, 'role' => 'owner']);
            }

            $peers = Employee::where('owner_id', $ownerId)
                ->where('user_id', '!=', $user->id)
                ->whereHas('user')
                ->with('user')
                ->get()
                ->map(fn($e) => [
                    'id' => $e->user_id,
                    'name' => $e->user->name,
                    'role' => strtolower($e->position) === 'technician' ? 'technician' : 'employee',
                ]);

            return response()->json(['success' => true, 'data' => $contacts->merge($peers)->values()]);
        }

        return response()->json(['success' => true, 'data' => []]);
    }
}
