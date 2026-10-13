<?php

namespace App\Events;

use App\Models\ChatGroup;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageEdited implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageData;

    public function __construct(ChatMessage $message)
    {
        $this->messageData = [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'chat_group_id' => $message->chat_group_id,
        ];
    }

    public function broadcastOn(): array
    {
        $channels = [];
        if ($this->messageData['chat_group_id']) {
            $group = ChatGroup::with('users')->find($this->messageData['chat_group_id']);
            if ($group) {
                foreach ($group->users as $user) {
                    if ($user->id !== auth()->id()) {
                        $channels[] = new PrivateChannel('chat.'.$user->id);
                    }
                }
            }
        } elseif ($this->messageData['receiver_id']) {
            $receiverId = $this->messageData['sender_id'] === auth()->id() ? $this->messageData['receiver_id'] : $this->messageData['sender_id'];
            $channels[] = new PrivateChannel('chat.'.$receiverId);
        }

        return $channels;
    }
}
