<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $receiverId, public string $userName)
    {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.'.$this->receiverId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'userName' => $this->userName,
        ];
    }
}
