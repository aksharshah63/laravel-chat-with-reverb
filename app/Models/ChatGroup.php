<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatGroup extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'avatar', 'owner_id'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_group_user')->withPivot('is_admin', 'last_read_at', 'cleared_at')->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'chat_group_id');
    }
}
