<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'is_read',
        'is_edited',
        'is_pinned',
        'is_deleted',
        'deleted_for_user_ids',
        'reply_to_id',
        'reactions',
        'attachments',
        'chat_group_id',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_deleted' => 'boolean',
        'deleted_for_user_ids' => 'array',
        'reactions' => 'array',
        'attachments' => 'array',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function chatGroup(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'chat_group_id');
    }

    public function repliedMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'reply_to_id');
    }
}
