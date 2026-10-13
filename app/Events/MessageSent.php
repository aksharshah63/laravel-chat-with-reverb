<?php

namespace App\Events;

use App\Models\ChatGroup;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public ChatMessage $message)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        if ($this->message->chat_group_id) {
            $group = ChatGroup::with('users')->find($this->message->chat_group_id);
            if ($group) {
                foreach ($group->users as $user) {
                    if ($user->id !== $this->message->sender_id) {
                        $channels[] = new PrivateChannel('chat.'.$user->id);
                    }
                }
            }
        } elseif ($this->message->receiver_id) {
            $channels[] = new PrivateChannel('chat.'.$this->message->receiver_id);
        }

        return $channels;
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'chat_group_id' => $this->message->chat_group_id,
            'message' => $this->message->message,
        ];
    }
}
