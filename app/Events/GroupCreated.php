<?php

namespace App\Events;

use App\Models\ChatGroup;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $group;

    public function __construct(ChatGroup $group)
    {
        $this->group = $group;
    }

    public function broadcastOn(): array
    {
        $channels = [];
        foreach ($this->group->users as $user) {
            if ($user->id !== $this->group->owner_id) {
                $channels[] = new PrivateChannel('chat.'.$user->id);
            }
        }

        return $channels;
    }

    public function broadcastWith()
    {
        return [
            'group_id' => $this->group->id,
        ];
    }
}
