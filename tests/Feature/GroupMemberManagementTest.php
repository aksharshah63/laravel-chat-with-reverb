<?php

use App\Livewire\Chat;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('owner can add and remove users from group', function () {
    Event::fake();

    $owner = User::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $group = ChatGroup::create([
        'name' => 'Test Group',
        'owner_id' => $owner->id,
    ]);
    $group->users()->attach($owner->id, ['is_admin' => true]);
    $group->users()->attach($user1->id, ['is_admin' => false]);

    Livewire::actingAs($owner)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->call('addUserToGroup', $user2->id)
        ->assertHasNoErrors();

    expect($group->fresh()->users->pluck('id'))->toContain($user2->id);

    Livewire::actingAs($owner)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->call('removeUserFromGroup', $user1->id)
        ->assertHasNoErrors();

    expect($group->fresh()->users->pluck('id'))->not->toContain($user1->id);
});

test('owner cannot be removed from group', function () {
    $owner = User::factory()->create();
    $user1 = User::factory()->create();

    $group = ChatGroup::create([
        'name' => 'Test Group',
        'owner_id' => $owner->id,
    ]);
    $group->users()->attach($owner->id, ['is_admin' => true]);
    $group->users()->attach($user1->id, ['is_admin' => false]);

    Livewire::actingAs($owner)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->call('removeUserFromGroup', $owner->id);

    expect($group->fresh()->users->pluck('id'))->toContain($owner->id);
});

test('user can clear 1-on-1 chat for themselves without deleting messages for the other user', function () {
    Event::fake();

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $msg = ChatMessage::create([
        'sender_id' => $user1->id,
        'receiver_id' => $user2->id,
        'message' => 'Hello',
    ]);

    Livewire::actingAs($user1)
        ->test(Chat::class)
        ->set('selectedUserId', $user2->id)
        ->call('openClearChatModal')
        ->assertSet('showClearChatModal', true)
        ->call('clearChat')
        ->assertHasNoErrors()
        ->assertSet('showClearChatModal', false)
        ->assertCount('messages', 0);

    // Message is NOT deleted from database
    expect(ChatMessage::count())->toBe(1);
    expect($msg->fresh()->deleted_for_user_ids)->toContain($user1->id);

    // user2 still sees the message
    Livewire::actingAs($user2)
        ->test(Chat::class)
        ->set('selectedUserId', $user1->id)
        ->assertCount('messages', 1)
        ->assertSee('Hello');
});

test('group admin or owner can rename group', function () {
    Event::fake();

    $owner = User::factory()->create();
    $group = ChatGroup::create([
        'name' => 'Original Group Name',
        'owner_id' => $owner->id,
    ]);
    $group->users()->attach($owner->id, ['is_admin' => true]);

    Livewire::actingAs($owner)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->call('openRenameGroupModal', $group->id)
        ->assertSet('editGroupName', 'Original Group Name')
        ->assertSet('showRenameGroupModal', true)
        ->set('editGroupName', 'Renamed Group Name')
        ->call('renameGroup')
        ->assertHasNoErrors()
        ->assertSet('showRenameGroupModal', false);

    expect($group->fresh()->name)->toBe('Renamed Group Name');
});

test('non-admin member cannot rename group', function () {
    Event::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create();

    $group = ChatGroup::create([
        'name' => 'Original Group Name',
        'owner_id' => $owner->id,
    ]);
    $group->users()->attach($owner->id, ['is_admin' => true]);
    $group->users()->attach($member->id, ['is_admin' => false]);

    Livewire::actingAs($member)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->set('editingGroupId', $group->id)
        ->set('editGroupName', 'Unauthorized Rename')
        ->call('renameGroup');

    expect($group->fresh()->name)->toBe('Original Group Name');
});

test('user can clear group chat messages for themselves without deleting messages for other members', function () {
    Event::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create();

    $group = ChatGroup::create([
        'name' => 'Test Group',
        'owner_id' => $owner->id,
    ]);
    $group->users()->attach($owner->id, ['is_admin' => true]);
    $group->users()->attach($member->id, ['is_admin' => false]);

    ChatMessage::create([
        'sender_id' => $owner->id,
        'chat_group_id' => $group->id,
        'message' => 'First group message',
    ]);
    ChatMessage::create([
        'sender_id' => $member->id,
        'chat_group_id' => $group->id,
        'message' => 'Second group message',
    ]);

    expect(ChatMessage::where('chat_group_id', $group->id)->count())->toBe(2);

    // Member opens modal and clears chat
    Livewire::actingAs($member)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->call('openClearChatModal')
        ->assertSet('showClearChatModal', true)
        ->call('clearChat')
        ->assertHasNoErrors()
        ->assertSet('showClearChatModal', false)
        ->assertCount('messages', 0);

    // Messages are NOT deleted from the database!
    expect(ChatMessage::where('chat_group_id', $group->id)->count())->toBe(2);

    // Group remains intact
    expect($group->fresh())->not->toBeNull();
    expect($group->fresh()->users->pluck('id'))->toContain($owner->id, $member->id);

    // Member's cleared_at is set on the pivot
    $memberPivot = $member->fresh()->chatGroups()->where('chat_group_id', $group->id)->first()->pivot;
    expect($memberPivot->cleared_at)->not->toBeNull();

    // Owner still sees all messages when viewing the group!
    Livewire::actingAs($owner)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->assertCount('messages', 2);
});

test('owner can delete group and all its messages', function () {
    Event::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create();

    $group = ChatGroup::create([
        'name' => 'To Be Deleted',
        'owner_id' => $owner->id,
    ]);
    $group->users()->attach($owner->id, ['is_admin' => true]);
    $group->users()->attach($member->id, ['is_admin' => false]);

    ChatMessage::create([
        'sender_id' => $owner->id,
        'chat_group_id' => $group->id,
        'message' => 'Message to be deleted with group',
    ]);

    Livewire::actingAs($owner)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->call('openDeleteGroupModal')
        ->assertSet('showDeleteGroupModal', true)
        ->call('deleteGroup')
        ->assertHasNoErrors()
        ->assertSet('showDeleteGroupModal', false);

    expect(ChatGroup::find($group->id))->toBeNull();
    expect(ChatMessage::where('chat_group_id', $group->id)->count())->toBe(0);
});

test('non-owner cannot open delete group modal', function () {
    Event::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create();

    $group = ChatGroup::create([
        'name' => 'Member Cannot Open Delete Modal',
        'owner_id' => $owner->id,
    ]);
    $group->users()->attach($owner->id, ['is_admin' => true]);
    $group->users()->attach($member->id, ['is_admin' => false]);

    Livewire::actingAs($member)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->call('openDeleteGroupModal')
        ->assertSet('showDeleteGroupModal', false);
});

test('non-owner cannot delete group', function () {
    Event::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create();

    $group = ChatGroup::create([
        'name' => 'Cannot Be Deleted By Member',
        'owner_id' => $owner->id,
    ]);
    $group->users()->attach($owner->id, ['is_admin' => true]);
    $group->users()->attach($member->id, ['is_admin' => false]);

    Livewire::actingAs($member)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->call('deleteGroup');

    expect(ChatGroup::find($group->id))->not->toBeNull();
});

test('member can open leave group modal and leave the group', function () {
    Event::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create();

    $group = ChatGroup::create([
        'name' => 'Leave Group Test',
        'owner_id' => $owner->id,
    ]);
    $group->users()->attach($owner->id, ['is_admin' => true]);
    $group->users()->attach($member->id, ['is_admin' => false]);

    Livewire::actingAs($member)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->call('openLeaveGroupModal')
        ->assertSet('showLeaveGroupModal', true)
        ->call('leaveGroup')
        ->assertHasNoErrors()
        ->assertSet('showLeaveGroupModal', false);

    expect($group->fresh()->users->pluck('id'))->not->toContain($member->id);
    expect($group->fresh()->users->pluck('id'))->toContain($owner->id);
});

test('owner cannot open leave group modal', function () {
    Event::fake();

    $owner = User::factory()->create();

    $group = ChatGroup::create([
        'name' => 'Owner Group',
        'owner_id' => $owner->id,
    ]);
    $group->users()->attach($owner->id, ['is_admin' => true]);

    Livewire::actingAs($owner)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->call('openLeaveGroupModal')
        ->assertSet('showLeaveGroupModal', false);
});

test('delete for me removes message from current user view only and other participant can still see it', function () {
    Event::fake();

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $msg = ChatMessage::create([
        'sender_id' => $user1->id,
        'receiver_id' => $user2->id,
        'message' => 'Visible to user2 only after delete for me',
    ]);

    // user1 opens delete modal and selects delete for me
    Livewire::actingAs($user1)
        ->test(Chat::class)
        ->set('selectedUserId', $user2->id)
        ->call('openDeleteMessageModal', $msg->id)
        ->assertSet('showDeleteMessageModal', true)
        ->assertSet('messageIdToDelete', $msg->id)
        ->call('deleteForMe')
        ->assertSet('showDeleteMessageModal', false)
        ->assertCount('messages', 0);

    // Message is still in database and not marked as deleted for everyone
    $freshMsg = $msg->fresh();
    expect($freshMsg->is_deleted)->toBeFalse();
    expect($freshMsg->message)->toBe('Visible to user2 only after delete for me');
    expect($freshMsg->deleted_for_user_ids)->toContain($user1->id);

    // user2 can still see the message with original content
    Livewire::actingAs($user2)
        ->test(Chat::class)
        ->set('selectedUserId', $user1->id)
        ->assertCount('messages', 1)
        ->assertSee('Visible to user2 only after delete for me');
});

test('delete for everyone removes message content and displays deleted placeholder for all participants', function () {
    Event::fake();

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $msg = ChatMessage::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'message' => 'Secret message to be deleted',
        'is_pinned' => true,
    ]);

    // sender opens delete modal: can delete for everyone is true
    Livewire::actingAs($sender)
        ->test(Chat::class)
        ->set('selectedUserId', $receiver->id)
        ->call('openDeleteMessageModal', $msg->id)
        ->assertSet('showDeleteMessageModal', true)
        ->assertSet('canDeleteForEveryone', true)
        ->call('deleteForEveryone')
        ->assertSet('showDeleteMessageModal', false)
        ->assertSee(__('This message was deleted.'));

    $freshMsg = $msg->fresh();
    expect($freshMsg->is_deleted)->toBeTrue();
    expect($freshMsg->message)->toBeNull();
    expect($freshMsg->is_pinned)->toBeFalse();

    // receiver also sees the deleted placeholder
    Livewire::actingAs($receiver)
        ->test(Chat::class)
        ->set('selectedUserId', $sender->id)
        ->assertSee(__('This message was deleted.'));
});

test('non-sender cannot delete for everyone in direct chat', function () {
    Event::fake();

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $msg = ChatMessage::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'message' => 'Sender message',
    ]);

    // receiver tries to open delete modal: canDeleteForEveryone is false
    Livewire::actingAs($receiver)
        ->test(Chat::class)
        ->set('selectedUserId', $sender->id)
        ->call('openDeleteMessageModal', $msg->id)
        ->assertSet('canDeleteForEveryone', false)
        ->call('deleteForEveryone');

    expect($msg->fresh()->is_deleted)->toBeFalse();
    expect($msg->fresh()->message)->toBe('Sender message');
});

test('group admin can delete for everyone in group chat', function () {
    Event::fake();

    $admin = User::factory()->create();
    $member = User::factory()->create();

    $group = ChatGroup::create([
        'name' => 'Moderated Group',
        'owner_id' => $admin->id,
    ]);
    $group->users()->attach($admin->id, ['is_admin' => true]);
    $group->users()->attach($member->id, ['is_admin' => false]);

    $msg = ChatMessage::create([
        'sender_id' => $member->id,
        'chat_group_id' => $group->id,
        'message' => 'Member inappropriate message',
    ]);

    // Admin can delete for everyone
    Livewire::actingAs($admin)
        ->test(Chat::class)
        ->set('selectedGroupId', $group->id)
        ->call('openDeleteMessageModal', $msg->id)
        ->assertSet('canDeleteForEveryone', true)
        ->call('deleteForEveryone')
        ->assertSet('showDeleteMessageModal', false);

    expect($msg->fresh()->is_deleted)->toBeTrue();
    expect($msg->fresh()->message)->toBeNull();
});

test('submitting empty message without attachments does not create a message', function () {
    Event::fake();

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    Livewire::actingAs($sender)
        ->test(Chat::class)
        ->set('selectedUserId', $receiver->id)
        ->set('newMessage', '   ')
        ->set('attachments', [])
        ->call('submit')
        ->assertHasNoErrors();

    expect(ChatMessage::count())->toBe(0);

    // With non-empty text, it creates a message
    Livewire::actingAs($sender)
        ->test(Chat::class)
        ->set('selectedUserId', $receiver->id)
        ->set('newMessage', 'Hello there!')
        ->call('submit')
        ->assertHasNoErrors();

    expect(ChatMessage::count())->toBe(1);
    expect(ChatMessage::first()->message)->toBe('Hello there!');
});
