<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $groupId;

    public $memberIds;

    public function __construct($groupId, array $memberIds)
    {
        $this->groupId = $groupId;
        $this->memberIds = $memberIds;
    }

    public function broadcastOn(): array
    {
        $channels = [];
        foreach ($this->memberIds as $userId) {
            $channels[] = new PrivateChannel('chat.'.$userId);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'group_id' => $this->groupId,
        ];
    }
}
