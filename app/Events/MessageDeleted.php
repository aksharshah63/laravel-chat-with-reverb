<?php

namespace App\Events;

use App\Models\ChatGroup;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageData;

    public $receiverId;

    public function __construct(ChatMessage|int|array $message)
    {
        if ($message instanceof ChatMessage) {
            $this->messageData = [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'chat_group_id' => $message->chat_group_id,
            ];
            $this->receiverId = null;
        } elseif (is_array($message)) {
            $this->messageData = $message;
            $this->receiverId = null;
        } else {
            $this->receiverId = (int) $message;
            $this->messageData = null;
        }
    }

    public function broadcastOn(): array
    {
        $channels = [];
        if ($this->messageData) {
            if (! empty($this->messageData['chat_group_id'])) {
                $group = ChatGroup::with('users')->find($this->messageData['chat_group_id']);
                if ($group) {
                    foreach ($group->users as $user) {
                        if ($user->id !== auth()->id()) {
                            $channels[] = new PrivateChannel('chat.'.$user->id);
                        }
                    }
                }
            } elseif (! empty($this->messageData['receiver_id'])) {
                $receiverId = ($this->messageData['sender_id'] ?? null) === auth()->id() ? $this->messageData['receiver_id'] : ($this->messageData['sender_id'] ?? $this->messageData['receiver_id']);
                $channels[] = new PrivateChannel('chat.'.$receiverId);
            }
        } elseif ($this->receiverId) {
            $channels[] = new PrivateChannel('chat.'.$this->receiverId);
        }

        return $channels;
    }
}
