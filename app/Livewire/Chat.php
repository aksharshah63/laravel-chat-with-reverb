<?php

namespace App\Livewire;

use App\Events\GroupCreated;
use App\Events\GroupDeleted;
use App\Events\GroupUpdated;
use App\Events\MessageDeleted;
use App\Events\MessageEdited;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class Chat extends Component
{
    use WithFileUploads;

    public $users = [];

    public $groups = [];

    public $selectedUserId;

    public $selectedGroupId;

    #[Computed]
    public function selectedUser()
    {
        return $this->selectedUserId ? User::find($this->selectedUserId) : null;
    }

    #[Computed]
    public function selectedGroup()
    {
        return $this->selectedGroupId ? ChatGroup::with('users')->find($this->selectedGroupId) : null;
    }

    public $showCreateGroupModal = false;

    public $showManageGroupModal = false;

    public $showRenameGroupModal = false;

    public $showClearChatModal = false;

    public $showDeleteGroupModal = false;

    public $showLeaveGroupModal = false;

    public $showDeleteMessageModal = false;

    public $messageIdToDelete = null;

    public $canDeleteForEveryone = false;

    public $confirmDeleteForEveryone = false;

    public $editingGroupId = null;

    public $editGroupName = '';

    public $newGroupName = '';

    public $newGroupMembers = [];

    public $newMessage;

    public $attachments = [];

    public $editingMessageId = null;

    public $editMessageText = '';

    public $replyingToMessageId = null;

    public $replyingToMessageText = '';

    public $replyingToMessageSender = '';

    public $showForwardModal = false;

    public $forwardMessageId = null;

    public $messages;

    public $authId;

    public $loginId;

    public $lastMessages = [];

    public $unreadCounts = [];

    public $searchQuery = '';

    public $filterType = 'all';

    public $pinnedUserIds = [];

    public function getFilteredUsers()
    {
        $filtered = collect($this->users);

        if ($this->filterType === 'unread') {
            $filtered = $filtered->filter(function ($user) {
                return ! empty($this->unreadCounts[$user->id]) && $this->unreadCounts[$user->id] > 0;
            });
        }

        if (! empty($this->searchQuery)) {
            $query = strtolower($this->searchQuery);
            $filtered = $filtered->filter(function ($user) use ($query) {
                $nameMatch = str_contains(strtolower($user->name), $query);
                $msgMatch = isset($this->lastMessages[$user->id]) && str_contains(strtolower($this->lastMessages[$user->id]), $query);

                return $nameMatch || $msgMatch;
            });
        }

        return $filtered->sortByDesc(function ($user) {
            return in_array($user->id, $this->pinnedUserIds) ? 1 : 0;
        })->values();
    }

    public function mount()
    {
        $this->loginId = auth()->id();
        $this->users = User::whereNot('id', $this->loginId)->latest()->get();
        $this->groups = auth()->user()->chatGroups()->get();
        if ($this->groups->count() > 0) {
            $this->selectedGroupId = $this->groups->first()->id;
            $this->selectedUserId = null;
        } else {
            $this->selectedUserId = $this->users->first()?->id;
            $this->selectedGroupId = null;
        }
        $this->loadMessages();
        $this->loadLastMessages();
        $this->loadUnreadCounts();
        $this->pinnedUserIds = DB::table('user_pins')
            ->where('user_id', $this->loginId)
            ->pluck('pinned_user_id')
            ->toArray();
    }

    public function loadLastMessages()
    {
        foreach ($this->users as $user) {
            $msg = ChatMessage::where(function ($query) use ($user) {
                $query->where('sender_id', $this->loginId)
                    ->where('receiver_id', $user->id);
            })->orWhere(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->where('receiver_id', $this->loginId);
            })->latest()->get()->filter(function ($m) {
                return ! in_array($this->loginId, $m->deleted_for_user_ids ?? []);
            })->first();

            $this->lastMessages[$user->id] = $msg ? ($msg->is_deleted ? __('This message was deleted') : $msg->message) : null;
        }

        foreach ($this->groups as $group) {
            $clearedAt = $group->pivot->cleared_at ?? null;

            $query = ChatMessage::where('chat_group_id', $group->id);
            if ($clearedAt) {
                $query->where('created_at', '>', $clearedAt);
            }
            $msg = $query->latest()->get()->filter(function ($m) {
                return ! in_array($this->loginId, $m->deleted_for_user_ids ?? []);
            })->first();

            $this->lastMessages['g'.$group->id] = $msg ? ($msg->is_deleted ? __('This message was deleted') : $msg->message) : null;
        }
    }

    public function loadUnreadCounts()
    {
        $counts = ChatMessage::where('receiver_id', $this->loginId)
            ->where('is_read', false)
            ->selectRaw('sender_id, count(*) as count')
            ->groupBy('sender_id')
            ->pluck('count', 'sender_id')
            ->toArray();

        foreach ($this->users as $user) {
            $this->unreadCounts[$user->id] = $counts[$user->id] ?? 0;
        }

        foreach ($this->groups as $group) {
            $lastRead = $group->pivot->last_read_at;
            $clearedAt = $group->pivot->cleared_at ?? null;

            $query = ChatMessage::where('chat_group_id', $group->id)
                ->where('sender_id', '!=', $this->loginId);

            if ($lastRead) {
                $query->where('created_at', '>', $lastRead);
            }
            if ($clearedAt) {
                $query->where('created_at', '>', $clearedAt);
            }

            $this->unreadCounts['g'.$group->id] = $query->count();
        }
    }

    public function markMessagesAsRead($userId)
    {
        $updated = ChatMessage::where('sender_id', $userId)
            ->where('receiver_id', $this->loginId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->unreadCounts[$userId] = 0;

        if ($updated > 0) {
            broadcast(new MessageRead($this->loginId, $userId));
        }
    }

    public function togglePin($userId)
    {
        if (in_array($userId, $this->pinnedUserIds)) {
            DB::table('user_pins')
                ->where('user_id', $this->loginId)
                ->where('pinned_user_id', $userId)
                ->delete();
            $this->pinnedUserIds = array_diff($this->pinnedUserIds, [$userId]);
        } else {
            DB::table('user_pins')->insert([
                'user_id' => $this->loginId,
                'pinned_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->pinnedUserIds[] = $userId;
        }
    }

    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
        $this->selectedGroupId = null;
        $this->loadMessages();
    }

    public function selectGroup($groupId)
    {
        $this->selectedGroupId = $groupId;
        $this->selectedUserId = null;
        $this->loadMessages();
    }

    public function saveNewGroup()
    {
        $this->validate([
            'newGroupName' => 'required|string|max:255',
            'newGroupMembers' => 'array',
        ]);

        $group = ChatGroup::create([
            'name' => trim($this->newGroupName),
            'owner_id' => auth()->id(),
        ]);

        $members = $this->newGroupMembers;
        $members[] = auth()->id(); // Always add creator
        $group->users()->sync($members);
        // Make creator admin
        $group->users()->updateExistingPivot(auth()->id(), ['is_admin' => true]);

        $group->load('users');
        broadcast(new GroupCreated($group));

        $this->groups = auth()->user()->chatGroups()->get();
        $this->selectGroup($group->id);

        $this->showCreateGroupModal = false;
        $this->newGroupName = '';
        $this->newGroupMembers = [];
    }

    public function loadMessages()
    {
        if ($this->selectedGroup) {
            $pivot = auth()->user()->chatGroups()
                ->where('chat_group_id', $this->selectedGroup->id)
                ->first()
                ?->pivot;

            $clearedAt = $pivot?->cleared_at;

            $query = ChatMessage::with(['repliedMessage.sender', 'sender'])
                ->where('chat_group_id', $this->selectedGroup->id);

            if ($clearedAt) {
                $query->where('created_at', '>', $clearedAt);
            }

            $this->messages = $query->get()->filter(function ($m) {
                return ! in_array($this->loginId, $m->deleted_for_user_ids ?? []);
            })->values();

            auth()->user()->chatGroups()->updateExistingPivot($this->selectedGroup->id, ['last_read_at' => now()]);
            $this->unreadCounts['g'.$this->selectedGroup->id] = 0;
        } elseif ($this->selectedUser) {
            $this->messages = ChatMessage::with(['repliedMessage.sender'])->where(function ($query) {
                $query->where('sender_id', auth()->id())
                    ->where('receiver_id', $this->selectedUser->id);
            })->orWhere(function ($query) {
                $query->where('sender_id', $this->selectedUser->id)
                    ->where('receiver_id', auth()->id());
            })->get()->filter(function ($m) {
                return ! in_array($this->loginId, $m->deleted_for_user_ids ?? []);
            })->values();

            $this->markMessagesAsRead($this->selectedUser->id);
        } else {
            $this->messages = collect();
        }
    }

    public function submit()
    {
        if ($this->editingMessageId) {
            $this->updateMessage();

            return;
        }

        $this->validate([
            'attachments.*' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,doc,docx,xls,xlsx,txt|max:5120',
        ]);

        $trimmedMessage = trim($this->newMessage ?? '');
        if (! $trimmedMessage && empty($this->attachments)) {
            return;
        }

        $savedAttachments = [];
        if (! empty($this->attachments)) {
            foreach ($this->attachments as $file) {
                $path = $file->store('chat-attachments', 'public');
                $savedAttachments[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        $message = ChatMessage::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $this->selectedGroup ? null : $this->selectedUser->id,
            'chat_group_id' => $this->selectedGroup ? $this->selectedGroup->id : null,
            'message' => $trimmedMessage ?: null,
            'reply_to_id' => $this->replyingToMessageId,
            'attachments' => count($savedAttachments) > 0 ? $savedAttachments : null,
        ]);

        $message->load('repliedMessage.sender');

        $this->messages->push($message);
        $targetId = $this->selectedGroup ? 'g'.$this->selectedGroup->id : $this->selectedUser->id;
        $this->lastMessages[$targetId] = $this->newMessage ?: (count($savedAttachments) > 0 ? __('Attachment') : '');
        $this->newMessage = '';
        $this->attachments = [];
        $this->cancelReply();

        broadcast(new MessageSent($message));
        $this->dispatch('scroll-bottom');
    }

    public function startEditMessage($messageId)
    {
        $message = ChatMessage::find($messageId);
        if ($message && $message->sender_id === $this->loginId && ! $message->is_deleted) {
            $this->editingMessageId = $messageId;
            $this->newMessage = $message->message;
            $this->cancelReply();
        }
    }

    public function cancelEdit()
    {
        $this->editingMessageId = null;
        $this->newMessage = '';
    }

    public function startReplyMessage($messageId)
    {
        $message = ChatMessage::with('sender')->find($messageId);
        if ($message && ! $message->is_deleted && ($message->sender_id === $this->loginId || $message->receiver_id === $this->loginId || $message->chat_group_id)) {
            $this->replyingToMessageId = $messageId;
            $this->replyingToMessageText = $message->message;
            $this->replyingToMessageSender = $message->sender_id === $this->loginId ? __('You') : $message->sender->name;
            $this->cancelEdit();
        }
    }

    public function cancelReply()
    {
        $this->replyingToMessageId = null;
        $this->replyingToMessageText = '';
        $this->replyingToMessageSender = '';
    }

    public function startForwardMessage($messageId)
    {
        $this->forwardMessageId = $messageId;
        $this->showForwardModal = true;
    }

    public function forwardMessageToUser($userId)
    {
        $this->executeForwardMessage(['receiver_id' => $userId]);
    }

    public function forwardMessageToGroup($groupId)
    {
        $this->executeForwardMessage(['chat_group_id' => $groupId]);
    }

    private function executeForwardMessage($target)
    {
        if (! $this->forwardMessageId) {
            return;
        }

        $originalMessage = ChatMessage::find($this->forwardMessageId);
        if (! $originalMessage) {
            return;
        }

        $message = ChatMessage::create(array_merge([
            'sender_id' => $this->loginId,
            'message' => $originalMessage->message,
            'attachments' => $originalMessage->attachments,
        ], $target));

        broadcast(new MessageSent($message));

        // If the forwarded target is the currently active chat, reload messages
        if (
            (isset($target['receiver_id']) && $this->selectedUserId == $target['receiver_id']) ||
            (isset($target['chat_group_id']) && $this->selectedGroupId == $target['chat_group_id'])
        ) {
            $this->loadMessages();
            $this->dispatch('scroll-bottom');
        } else {
            // Otherwise, just update the last messages and unread counts
            $targetId = isset($target['chat_group_id']) ? 'g'.$target['chat_group_id'] : $target['receiver_id'];
            $this->lastMessages[$targetId] = $message->message ?: (count($message->attachments ?? []) > 0 ? __('Attachment') : '');
        }

        $this->showForwardModal = false;
        $this->forwardMessageId = null;
    }

    public function updateMessage()
    {
        if (! $this->editingMessageId || empty(trim($this->newMessage))) {
            return;
        }

        $message = ChatMessage::find($this->editingMessageId);
        if ($message && $message->sender_id === $this->loginId) {
            $message->message = trim($this->newMessage);
            $message->is_edited = true;
            $message->save();

            broadcast(new MessageEdited($message));
            $this->loadMessages();
            $this->cancelEdit();
        }
    }

    public function togglePinMessage($messageId)
    {
        $message = ChatMessage::find($messageId);
        if (! $message || $message->is_deleted) {
            return;
        }

        if ($message->sender_id !== $this->loginId && $message->receiver_id !== $this->loginId && ! $message->chat_group_id) {
            return;
        }

        $message->is_pinned = ! $message->is_pinned;
        $message->save();

        broadcast(new MessageEdited($message));
        $this->loadMessages();
    }

    public function toggleReaction($messageId, $emoji)
    {
        $message = ChatMessage::find($messageId);
        if (! $message || $message->is_deleted) {
            return;
        }

        if ($message->sender_id !== $this->loginId && $message->receiver_id !== $this->loginId && ! $message->chat_group_id) {
            return;
        }

        $reactions = $message->reactions ?? [];

        if (isset($reactions[$this->loginId]) && $reactions[$this->loginId] === $emoji) {
            unset($reactions[$this->loginId]);
        } else {
            $reactions[$this->loginId] = $emoji;
        }

        $message->reactions = $reactions;
        $message->save();

        broadcast(new MessageEdited($message));
        $this->loadMessages();
    }

    public function openDeleteMessageModal($messageId): void
    {
        $message = ChatMessage::find($messageId);
        if (! $message) {
            return;
        }

        $hasAccess = $message->sender_id === $this->loginId ||
            $message->receiver_id === $this->loginId ||
            ($message->chat_group_id && $this->groups->contains('id', $message->chat_group_id));

        if (! $hasAccess) {
            return;
        }

        $this->messageIdToDelete = $messageId;
        $this->confirmDeleteForEveryone = false;

        $isSender = $message->sender_id === $this->loginId;
        $isGroupAdmin = false;
        if ($message->chat_group_id) {
            $group = $this->groups->firstWhere('id', $message->chat_group_id);
            $isGroupAdmin = $group && ($group->owner_id === $this->loginId || ($group->pivot->is_admin ?? false));
        }

        $this->canDeleteForEveryone = ($isSender || $isGroupAdmin) && ! $message->is_deleted;
        $this->showDeleteMessageModal = true;
    }

    public function deleteForMe(): void
    {
        if (! $this->messageIdToDelete) {
            return;
        }

        $message = ChatMessage::find($this->messageIdToDelete);
        if ($message) {
            $deletedFor = $message->deleted_for_user_ids ?? [];
            if (! in_array($this->loginId, $deletedFor)) {
                $deletedFor[] = $this->loginId;
                $message->deleted_for_user_ids = array_values($deletedFor);
                $message->save();
            }

            $this->loadMessages();
            $this->loadLastMessages();
        }

        $this->showDeleteMessageModal = false;
        $this->messageIdToDelete = null;
        $this->confirmDeleteForEveryone = false;
    }

    public function deleteForEveryone(): void
    {
        if (! $this->messageIdToDelete) {
            return;
        }

        $message = ChatMessage::find($this->messageIdToDelete);
        if (! $message) {
            return;
        }

        $isSender = $message->sender_id === $this->loginId;
        $isGroupAdmin = false;
        if ($message->chat_group_id) {
            $group = $this->groups->firstWhere('id', $message->chat_group_id);
            $isGroupAdmin = $group && ($group->owner_id === $this->loginId || ($group->pivot->is_admin ?? false));
        }

        if (! $isSender && ! $isGroupAdmin) {
            return;
        }

        $message->is_deleted = true;
        $message->message = null;
        $message->attachments = null;
        $message->reactions = null;
        $message->is_pinned = false;
        $message->save();

        broadcast(new MessageDeleted($message));

        $this->loadMessages();
        $this->loadLastMessages();

        $this->showDeleteMessageModal = false;
        $this->messageIdToDelete = null;
        $this->confirmDeleteForEveryone = false;
    }

    public function deleteMessage($messageId): void
    {
        $this->openDeleteMessageModal($messageId);
    }

    public function updatedNewMessage($value)
    {
        if ($this->selectedUser) {
            broadcast(new UserTyping($this->selectedUser->id, Auth::user()->name));
        }
    }

    public function getListeners()
    {
        return [
            "echo-private:chat.{$this->loginId},MessageSent" => 'newChatMessageNotification',
            "echo-private:chat.{$this->loginId},MessageRead" => 'chatMessageReadNotification',
            "echo-private:chat.{$this->loginId},MessageEdited" => 'chatMessageEditedNotification',
            "echo-private:chat.{$this->loginId},MessageDeleted" => 'chatMessageDeletedNotification',
            "echo-private:chat.{$this->loginId},GroupDeleted" => 'chatGroupDeletedNotification',
            "echo-private:chat.{$this->loginId},GroupCreated" => 'chatGroupCreatedNotification',
            "echo-private:chat.{$this->loginId},GroupUpdated" => 'chatGroupUpdatedNotification',
        ];
    }

    public function chatGroupCreatedNotification($event)
    {
        $this->groups = auth()->user()->chatGroups()->get();
    }

    public function chatGroupUpdatedNotification($event)
    {
        $this->groups = auth()->user()->chatGroups()->get();
        if ($this->selectedGroupId && $this->selectedGroupId == $event['group_id']) {
            unset($this->selectedGroup);
        }
    }

    public function chatGroupDeletedNotification($event)
    {
        $groupId = $event['group_id'];

        $this->groups = auth()->user()->chatGroups()->get();

        if ($this->selectedGroup && $this->selectedGroup->id == $groupId) {
            $this->selectedGroupId = null;
            $this->selectedUserId = $this->users->first()?->id;
            $this->loadMessages();
        }
    }

    public function chatMessageEditedNotification($event)
    {
        $this->loadMessages();
    }

    public function chatMessageDeletedNotification($event)
    {
        $payload = $event['messageData'] ?? $event;
        if (! isset($payload['id'])) {
            if (isset($payload['chat_group_id']) && $payload['chat_group_id']) {
                $this->lastMessages['g'.$payload['chat_group_id']] = '';
            } elseif (isset($payload['receiver_id']) || isset($payload['sender_id'])) {
                $otherId = ($payload['sender_id'] ?? null) === auth()->id()
                    ? ($payload['receiver_id'] ?? null)
                    : ($payload['sender_id'] ?? $payload['receiver_id'] ?? null);
                if ($otherId) {
                    $this->lastMessages[$otherId] = '';
                }
            }
        }
        $this->loadMessages();
        $this->loadLastMessages();
    }

    public function chatMessageReadNotification($event)
    {
        if ($this->selectedUser && $this->selectedUser->id == $event['receiverId']) {
            $this->loadMessages();
        }
    }

    public function newChatMessageNotification($message)
    {
        if (isset($message['chat_group_id']) && $message['chat_group_id']) {
            $groupId = $message['chat_group_id'];
            $this->lastMessages['g'.$groupId] = $message['message'];

            if (! $this->groups->contains('id', $groupId)) {
                $this->groups = auth()->user()->chatGroups()->get();
            }

            if ($this->selectedGroup && $groupId == $this->selectedGroup->id) {
                $messageObj = ChatMessage::with(['repliedMessage.sender', 'sender'])->find($message['id']);
                $this->messages->push($messageObj);
                auth()->user()->chatGroups()->updateExistingPivot($groupId, ['last_read_at' => now()]);
                $this->unreadCounts['g'.$groupId] = 0;
            } else {
                $this->unreadCounts['g'.$groupId] = ($this->unreadCounts['g'.$groupId] ?? 0) + 1;
            }
        } else {
            $this->lastMessages[$message['sender_id']] = $message['message'];

            if ($this->selectedUser && $message['sender_id'] == $this->selectedUser->id) {
                $messageObj = ChatMessage::with(['repliedMessage.sender'])->find($message['id']);
                $this->messages->push($messageObj);
                $this->markMessagesAsRead($message['sender_id']);
            } else {
                $this->unreadCounts[$message['sender_id']] = ($this->unreadCounts[$message['sender_id']] ?? 0) + 1;
            }
        }
    }

    public function render()
    {
        return view('livewire.chat');
    }

    public function clearChat()
    {
        if ($this->selectedGroup) {
            $isMember = $this->selectedGroup->owner_id === auth()->id() ||
                $this->selectedGroup->users()->where('user_id', auth()->id())->exists();

            if (! $isMember) {
                return;
            }

            $groupId = $this->selectedGroup->id;

            auth()->user()->chatGroups()->updateExistingPivot($groupId, [
                'cleared_at' => now(),
            ]);

            $this->groups = auth()->user()->chatGroups()->get();
            $this->lastMessages['g'.$groupId] = '';
            $this->loadMessages();
            $this->showClearChatModal = false;

            return;
        }

        if (! $this->selectedUser) {
            return;
        }

        $userId = $this->selectedUser->id;

        $messages = ChatMessage::where(function ($query) use ($userId) {
            $query->where('sender_id', auth()->id())
                ->where('receiver_id', $userId);
        })->orWhere(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', auth()->id());
        })->get();

        foreach ($messages as $msg) {
            $deletedFor = $msg->deleted_for_user_ids ?? [];
            if (! in_array(auth()->id(), $deletedFor)) {
                $deletedFor[] = auth()->id();
                $msg->deleted_for_user_ids = array_values($deletedFor);
                $msg->save();
            }
        }

        $this->lastMessages[$userId] = '';
        $this->loadMessages();
        $this->loadLastMessages();
        $this->showClearChatModal = false;
    }

    public function openClearChatModal(): void
    {
        $this->showClearChatModal = true;
    }

    public function openDeleteGroupModal(): void
    {
        if (! $this->selectedGroup || $this->selectedGroup->owner_id !== auth()->id()) {
            return;
        }

        $this->showManageGroupModal = false;
        $this->showDeleteGroupModal = true;
    }

    public function deleteGroup(): void
    {
        if (! $this->selectedGroup || $this->selectedGroup->owner_id !== auth()->id()) {
            return;
        }

        $groupId = $this->selectedGroup->id;
        $memberIds = $this->selectedGroup->users->pluck('id')->toArray();

        ChatMessage::where('chat_group_id', $groupId)->delete();

        $this->selectedGroup->delete();

        broadcast(new GroupDeleted($groupId, $memberIds));

        $this->showManageGroupModal = false;
        $this->showDeleteGroupModal = false;
        $this->groups = auth()->user()->chatGroups()->get();
        unset($this->lastMessages['g'.$groupId]);
        $this->selectedGroupId = null;
        $this->selectedUserId = $this->users->first()?->id;
        $this->loadMessages();
    }

    public function removeAttachment($index)
    {
        array_splice($this->attachments, $index, 1);
    }

    public function isGroupAdmin(): bool
    {
        if (! $this->selectedGroup) {
            return false;
        }

        if ($this->selectedGroup->owner_id === auth()->id()) {
            return true;
        }

        return $this->selectedGroup->users()
            ->where('user_id', auth()->id())
            ->wherePivot('is_admin', true)
            ->exists();
    }

    public function addUserToGroup($userId)
    {
        if (! $this->selectedGroup || ! $this->isGroupAdmin()) {
            return;
        }

        $group = $this->selectedGroup;
        if ($group->users()->where('user_id', $userId)->exists()) {
            return;
        }

        $group->users()->attach($userId, [
            'is_admin' => false,
            'last_read_at' => now(),
        ]);

        $group->load('users');
        $allMemberIds = $group->users->pluck('id')->toArray();

        broadcast(new GroupCreated($group));
        broadcast(new GroupUpdated($group->id, $allMemberIds));

        $this->refreshGroupState();
    }

    public function removeUserFromGroup($userId)
    {
        if (! $this->selectedGroup || ! $this->isGroupAdmin()) {
            return;
        }

        $group = $this->selectedGroup;

        if ($group->owner_id == $userId) {
            return;
        }

        $allMemberIds = $group->users->pluck('id')->toArray();

        $group->users()->detach($userId);

        broadcast(new GroupDeleted($group->id, [$userId]));

        $remainingMemberIds = array_values(array_diff($allMemberIds, [$userId]));
        broadcast(new GroupUpdated($group->id, $remainingMemberIds));

        $this->refreshGroupState();
    }

    public function openLeaveGroupModal(): void
    {
        if (! $this->selectedGroup || $this->selectedGroup->owner_id === auth()->id()) {
            return;
        }

        $this->showManageGroupModal = false;
        $this->showLeaveGroupModal = true;
    }

    public function leaveGroup()
    {
        if (! $this->selectedGroup) {
            return;
        }

        $group = $this->selectedGroup;
        $userId = auth()->id();

        if ($group->owner_id == $userId) {
            return;
        }

        $allMemberIds = $group->users->pluck('id')->toArray();

        $group->users()->detach($userId);

        broadcast(new GroupDeleted($group->id, [$userId]));

        $remainingMemberIds = array_values(array_diff($allMemberIds, [$userId]));
        broadcast(new GroupUpdated($group->id, $remainingMemberIds));

        $this->showManageGroupModal = false;
        $this->showLeaveGroupModal = false;
        $this->groups = auth()->user()->chatGroups()->get();
        $this->selectedGroupId = null;
        $this->selectedUserId = $this->users->first()?->id;
        $this->loadMessages();
    }

    public function refreshGroupState()
    {
        $this->groups = auth()->user()->chatGroups()->get();
        unset($this->selectedGroup);
    }

    public function openRenameGroupModal(?int $groupId = null): void
    {
        $group = $groupId ? ChatGroup::find($groupId) : $this->selectedGroup;

        if (! $group) {
            return;
        }

        $isAdmin = $group->owner_id === auth()->id() || $group->users()
            ->where('user_id', auth()->id())
            ->wherePivot('is_admin', true)
            ->exists();

        if (! $isAdmin) {
            return;
        }

        $this->editingGroupId = $group->id;
        $this->editGroupName = $group->name;
        $this->showRenameGroupModal = true;
    }

    public function renameGroup(): void
    {
        $this->validate([
            'editGroupName' => 'required|string|min:1|max:255',
        ]);

        $group = $this->editingGroupId ? ChatGroup::with('users')->find($this->editingGroupId) : $this->selectedGroup;

        if (! $group) {
            return;
        }

        $isAdmin = $group->owner_id === auth()->id() || $group->users()
            ->where('user_id', auth()->id())
            ->wherePivot('is_admin', true)
            ->exists();

        if (! $isAdmin) {
            return;
        }

        $group->update([
            'name' => trim($this->editGroupName),
        ]);

        $memberIds = $group->users->pluck('id')->toArray();
        broadcast(new GroupUpdated($group->id, $memberIds));

        $this->refreshGroupState();
        $this->showRenameGroupModal = false;
        $this->editGroupName = '';
        $this->editingGroupId = null;
    }
}
