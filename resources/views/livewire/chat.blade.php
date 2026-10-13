<div x-data="{
    onlineUsers: [],
    mobileShowChat: false,
    init() {
        window.Echo.join('online')
            .here((users) => {
                this.onlineUsers = users.map(u => u.id);
            })
            .joining((user) => {
                if (!this.onlineUsers.includes(user.id)) {
                    this.onlineUsers = [...this.onlineUsers, user.id];
                }
            })
            .leaving((user) => {
                this.onlineUsers = this.onlineUsers.filter(id => id !== user.id);
            });
    }
}">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Chat') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6 text-zinc-500 dark:text-zinc-400">
            {{ __('Connect and converse seamlessly') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div
        class="flex h-[85vh] md:h-[650px] text-sm border border-zinc-200 dark:border-zinc-700/80 rounded-2xl shadow-sm overflow-hidden bg-white dark:bg-zinc-900">

        <!-- Left: User List -->
        <div class="w-full md:w-1/3 lg:w-[350px] shrink-0 border-r border-zinc-200 dark:border-zinc-700/80 bg-zinc-50/50 dark:bg-zinc-800/20 flex-col"
            :class="mobileShowChat ? 'hidden md:flex' : 'flex'">
            <div
                class="p-5 border-b border-zinc-200 dark:border-zinc-700/80 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm">
                <div class="flex justify-between items-center mb-3">
                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Conversations') }}</span>
                    <span
                        class="bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400 py-0.5 px-2.5 rounded-full text-xs font-bold">{{ count($this->getFilteredUsers()) }}</span>
                </div>

                <div class="relative mb-3">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <flux:icon name="magnifying-glass" class="w-4 h-4 text-zinc-400" />
                    </div>
                    <input wire:model.live.debounce.150ms="searchQuery" type="text"
                        placeholder="{{ __('Search users or messages...') }}"
                        class="w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-9 pr-4 py-2 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 outline-none shadow-sm transition-all">
                </div>

                <div class="flex items-center gap-2">
                    <button wire:click="$set('filterType', 'all')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-medium transition-colors cursor-pointer {{ $filterType === 'all' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 border border-transparent' }}">
                        {{ __('All') }}
                    </button>
                    <button wire:click="$set('filterType', 'unread')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-medium transition-colors cursor-pointer {{ $filterType === 'unread' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 border border-transparent' }}">
                        {{ __('Unread') }}
                    </button>
                    <button wire:click="$set('filterType', 'groups')"
                        class="px-3.5 py-1.5 rounded-full text-xs font-medium transition-colors cursor-pointer {{ $filterType === 'groups' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 border border-transparent' }}">
                        {{ __('Groups') }}
                    </button>
                </div>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800/50 overflow-y-auto flex-1 custom-scrollbar">

                @if (in_array($filterType, ['all', 'groups']))
                    <!-- Groups Section -->
                    <div
                        class="px-4 py-2 bg-zinc-50 dark:bg-zinc-800/30 flex items-center justify-between sticky top-0 z-10 border-b border-zinc-100 dark:border-zinc-800/50">
                        <span
                            class="text-xs font-bold text-zinc-500 uppercase tracking-wider">{{ __('Groups') }}</span>
                        <button wire:click="$set('showCreateGroupModal', true)"
                            class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 p-1 rounded-full hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors"
                            title="{{ __('New Group') }}">
                            <flux:icon name="plus" class="w-4 h-4" />
                        </button>
                    </div>

                    @forelse ($groups as $group)
                        <div wire:key="group-{{ $group->id }}" wire:click="selectGroup({{ $group->id }})"
                            @click="mobileShowChat = true"
                            class="p-4 cursor-pointer transition-all duration-200 flex items-center gap-3 relative group {{ $this->selectedGroup?->id === $group->id ? 'bg-white dark:bg-zinc-800/80 shadow-sm' : 'hover:bg-white/60 dark:hover:bg-zinc-800/50' }}">

                            @if ($this->selectedGroup?->id === $group->id)
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500 rounded-r-full"></div>
                            @endif

                            <div
                                class="relative w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                                <flux:icon name="user-group" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center mb-0.5">
                                    <div
                                        class="text-zinc-900 dark:text-zinc-100 text-sm truncate {{ $this->selectedGroup?->id === $group->id ? 'font-semibold' : 'font-medium' }}">
                                        {{ $group->name }}
                                    </div>
                                    @if (!empty($unreadCounts['g' . $group->id]))
                                        <span
                                            class="bg-indigo-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 shadow-sm">
                                            {{ $unreadCounts['g' . $group->id] > 99 ? '99+' : $unreadCounts['g' . $group->id] }}
                                        </span>
                                    @endif
                                </div>
                                <div
                                    class="text-xs truncate {{ !empty($unreadCounts['g' . $group->id]) ? 'font-semibold text-zinc-800 dark:text-zinc-200' : 'text-zinc-500 dark:text-zinc-400' }}">
                                    @if (!empty($lastMessages['g' . $group->id]))
                                        {{ Str::limit($lastMessages['g' . $group->id], 40) }}
                                    @else
                                        <span class="italic opacity-75">{{ __('No messages yet') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-3 text-xs text-zinc-500 text-center italic">
                            {{ __('No groups yet.') }}
                        </div>
                    @endforelse

                @endif

                @if (in_array($filterType, ['all', 'unread']))
                    <!-- Direct Messages Section -->
                    <div
                        class="px-4 py-2 bg-zinc-50 dark:bg-zinc-800/30 flex items-center justify-between sticky top-0 z-10 border-b border-zinc-100 dark:border-zinc-800/50 border-t">
                        <span
                            class="text-xs font-bold text-zinc-500 uppercase tracking-wider">{{ __('Direct Messages') }}</span>
                    </div>

                    @forelse ($this->getFilteredUsers() as $user)
                        <div wire:key="user-{{ $user->id }}" wire:click="selectUser({{ $user->id }})"
                            @click="mobileShowChat = true"
                            class="p-4 cursor-pointer transition-all duration-200 flex items-center gap-3 relative group {{ $this->selectedUser?->id === $user->id ? 'bg-white dark:bg-zinc-800/80 shadow-sm' : 'hover:bg-white/60 dark:hover:bg-zinc-800/50' }}">

                            @if ($this->selectedUser?->id === $user->id)
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500 rounded-r-full"></div>
                            @endif

                            <div class="relative">
                                <flux:avatar size="sm" :name="$user->name" :src="$user->avatar" />

                                <span x-show="onlineUsers.includes({{ $user->id }})" style="display: none;"
                                    class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white dark:border-zinc-900 rounded-full"></span>

                                <span x-show="!onlineUsers.includes({{ $user->id }})" style="display: none;"
                                    class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-zinc-300 dark:bg-zinc-600 border-2 border-white dark:border-zinc-900 rounded-full"></span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center mb-0.5">
                                    <div
                                        class="text-zinc-900 dark:text-zinc-100 text-sm truncate {{ $this->selectedUser?->id === $user->id ? 'font-semibold' : 'font-medium' }}">
                                        {{ $user->name }}
                                    </div>
                                    @if (!empty($unreadCounts[$user->id]))
                                        <span
                                            class="bg-indigo-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 shadow-sm">
                                            {{ $unreadCounts[$user->id] > 99 ? '99+' : $unreadCounts[$user->id] }}
                                        </span>
                                    @endif
                                </div>
                                <div
                                    class="text-xs truncate {{ !empty($unreadCounts[$user->id]) ? 'font-semibold text-zinc-800 dark:text-zinc-200' : 'text-zinc-500 dark:text-zinc-400' }}">
                                    @if (!empty($lastMessages[$user->id]))
                                        {{ Str::limit($lastMessages[$user->id], 40) }}
                                    @else
                                        <span class="italic opacity-75">{{ __('No messages yet') }}</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Pin/Unpin Action -->
                            <div class="shrink-0 flex items-center ml-2">
                                <button wire:click.stop="togglePin({{ $user->id }})"
                                    class="p-1.5 rounded-full transition-colors {{ in_array($user->id, $pinnedUserIds) ? 'text-amber-500 hover:bg-amber-100 dark:hover:bg-amber-500/20' : 'text-zinc-300 dark:text-zinc-600 hover:text-zinc-500 dark:hover:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700/50 opacity-0 group-hover:opacity-100' }}"
                                    title="{{ in_array($user->id, $pinnedUserIds) ? 'Unpin conversation' : 'Pin conversation' }}">
                                    @if (in_array($user->id, $pinnedUserIds))
                                        <!-- Solid Pin -->
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" class="w-4 h-4">
                                            <path d="M12 17v5"></path>
                                            <path
                                                d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z">
                                            </path>
                                        </svg>
                                    @else
                                        <!-- Outline Pin -->
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" class="w-4 h-4">
                                            <path d="M12 17v5"></path>
                                            <path
                                                d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z">
                                            </path>
                                        </svg>
                                    @endif
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center p-8 text-center space-y-3">
                            @if (!empty($searchQuery))
                                <div
                                    class="w-12 h-12 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center">
                                    <flux:icon name="magnifying-glass" class="w-5 h-5 text-zinc-400" />
                                </div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('No matches found for ":query"', ['query' => $searchQuery]) }}
                                </p>
                            @elseif ($filterType === 'unread')
                                <div
                                    class="w-12 h-12 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center">
                                    <flux:icon name="check-circle" class="w-6 h-6 text-emerald-500" />
                                </div>
                                <p class="font-medium text-sm text-zinc-800 dark:text-zinc-200">
                                    {{ __('You\'re all caught up!') }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('No unread messages.') }}
                                </p>
                            @else
                                <div
                                    class="w-12 h-12 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center">
                                    <flux:icon name="chat-bubble-left-right" class="w-5 h-5 text-zinc-400" />
                                </div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('No conversations yet.') }}
                                </p>
                            @endif
                        </div>
                    @endforelse
                @endif
            </div>
        </div>

        <!-- Right: Chat Box -->
        <div class="w-full md:w-2/3 lg:flex-1 flex-col bg-white dark:bg-zinc-900 relative"
            :class="mobileShowChat ? 'flex' : 'hidden md:flex'">

            @php
                $formatMentions = function ($text, $isSent = false) {
                    $escaped = e(trim($text ?? ''));
                    $pattern = '/(?<=^|[\s,.:;!?(])(@[\p{L}\p{N}_\-\.]+(?:\s[\p{L}\p{N}_\-\.]+)?)(?=[,?!.:;\s]|$)/u';
                    $formatted = preg_replace_callback(
                        $pattern,
                        function ($matches) use ($isSent) {
                            $tag = $matches[1];
                            $cleanName = ltrim($tag, '@');
                            $isMe = auth()->check() && strcasecmp($cleanName, auth()->user()->name) === 0;

                            if ($isMe) {
                                $cls = $isSent
                                    ? 'font-bold text-amber-200 bg-amber-500/40 px-1.5 py-0.5 rounded-md inline-block my-0.5'
                                    : 'font-bold text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-950/80 border border-amber-300 dark:border-amber-700/60 px-1.5 py-0.5 rounded-md inline-block my-0.5';
                            } else {
                                $cls = $isSent
                                    ? 'font-semibold text-indigo-100 bg-indigo-700/60 dark:bg-indigo-900/60 px-1.5 py-0.5 rounded-md inline-block my-0.5'
                                    : 'font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-1.5 py-0.5 rounded-md inline-block my-0.5';
                            }

                            return '<span class="' . $cls . '">' . $tag . '</span>';
                        },
                        $escaped,
                    );

                    return nl2br($formatted);
                };

                $avatarColors = [
                    '#4f46e5',
                    '#7c3aed',
                    '#db2777',
                    '#d97706',
                    '#059669',
                    '#0891b2',
                    '#2563eb',
                    '#e11d48',
                    '#475569',
                ];
                $mapTaggableUser = function ($u) use ($avatarColors) {
                    $colorIndex = abs(crc32($u->name ?? (string) $u->id)) % count($avatarColors);
                    $initials = method_exists($u, 'initials')
                        ? $u->initials()
                        : strtoupper(substr(trim($u->name ?? 'U'), 0, 2));

                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'avatar' => $u->avatar ? \Illuminate\Support\Facades\Storage::url($u->avatar) : null,
                        'initials' => $initials,
                        'color' => $avatarColors[$colorIndex],
                        'email' => $u->email,
                    ];
                };

                $taggableUsers = [];
                if ($this->selectedGroup) {
                    $taggableUsers = $this->selectedGroup->users->map($mapTaggableUser)->values()->toArray();
                } elseif ($this->selectedUser) {
                    $taggableUsers = [$mapTaggableUser($this->selectedUser)];
                } else {
                    $taggableUsers = $users->map($mapTaggableUser)->values()->toArray();
                }
            @endphp

            <!-- Header -->
            <div class="p-4 md:p-5 border-b border-zinc-200 dark:border-zinc-700/80 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md flex justify-between items-center z-10 min-h-[76px] relative"
                x-data="chatSearchComponent()" @keydown.escape.window="closeSearch()"
                @keydown.ctrl.f.window.prevent="openSearch()" @keydown.meta.f.window.prevent="openSearch()">

                <div class="flex items-center gap-3 md:gap-4">
                    <button @click="mobileShowChat = false"
                        class="md:hidden shrink-0 p-1.5 -ml-1.5 rounded-full text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                            class="w-6 h-6">
                            <path fill-rule="evenodd"
                                d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    @if ($this->selectedGroup)
                        <div wire:click="$set('showManageGroupModal', true)"
                            class="relative w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0 cursor-pointer hover:opacity-90 transition-opacity"
                            title="{{ __('Manage Group Members') }}">
                            <flux:icon name="user-group" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div wire:key="header-info-g-{{ $this->selectedGroup->id }}"
                            wire:click="$set('showManageGroupModal', true)"
                            class="cursor-pointer group/header hover:opacity-90 transition-opacity"
                            title="{{ __('Manage Group Members') }}">
                            <div
                                class="text-base font-semibold text-zinc-800 dark:text-zinc-100 flex items-center gap-1.5">
                                <span>{{ $this->selectedGroup->name }}</span>
                                @if ($this->isGroupAdmin())
                                    <button type="button"
                                        wire:click.stop="openRenameGroupModal({{ $this->selectedGroup->id }})"
                                        class="text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors p-0.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer"
                                        title="{{ __('Rename Group') }}">
                                        <flux:icon name="pencil-square" class="w-3.5 h-3.5" />
                                    </button>
                                @endif
                                <flux:icon name="information-circle"
                                    class="w-4 h-4 text-zinc-400 group-hover/header:text-indigo-500 transition-colors" />
                            </div>
                            <div
                                class="text-xs text-zinc-500 font-medium mt-0.5 flex items-center gap-1.5 truncate max-w-[200px] md:max-w-md">
                                @php
                                    $memberNames = $this->selectedGroup->users->pluck('name');
                                @endphp
                                <span class="truncate" title="{{ $memberNames->implode(', ') }}">
                                    {{ $memberNames->take(4)->implode(', ') }}
                                    @if ($memberNames->count() > 4)
                                        {{ __('and :count more', ['count' => $memberNames->count() - 4]) }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    @elseif ($this->selectedUser)
                        <div class="relative" wire:key="header-avatar-{{ $this->selectedUser->id }}">
                            <flux:avatar size="md" :name="$this->selectedUser->name"
                                :src="$this->selectedUser->avatar" />
                            <span x-show="onlineUsers.includes({{ $this->selectedUser->id }})" style="display: none;"
                                class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white dark:border-zinc-900 rounded-full"></span>

                            <span x-show="!onlineUsers.includes({{ $this->selectedUser->id }})"
                                style="display: none;"
                                class="absolute bottom-0 right-0 w-3 h-3 bg-zinc-300 dark:bg-zinc-600 border-2 border-white dark:border-zinc-900 rounded-full"></span>
                        </div>
                        <div wire:key="header-info-{{ $this->selectedUser->id }}">
                            <div class="text-base font-semibold text-zinc-800 dark:text-zinc-100">
                                {{ $this->selectedUser->name }}
                            </div>

                            <div x-show="onlineUsers.includes({{ $this->selectedUser->id }})" style="display: none;"
                                class="text-xs text-green-500 font-medium mt-0.5 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                Online
                            </div>

                            <div x-show="!onlineUsers.includes({{ $this->selectedUser->id }})" style="display: none;"
                                class="text-xs text-zinc-500 font-medium mt-0.5 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 bg-zinc-400 dark:bg-zinc-500 rounded-full"></span>
                                Offline
                            </div>
                        </div>
                    @else
                        <div class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ __('Select a conversation') }}</div>
                    @endif
                </div>

                @if ($this->selectedGroup || $this->selectedUser)
                    <div class="flex items-center gap-1.5 ml-auto relative">
                        <!-- Active Search Input / Bar -->
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            class="flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl px-2.5 py-1.5 shadow-sm">

                            <flux:icon name="magnifying-glass" class="w-4 h-4 text-zinc-400 shrink-0" />

                            <input x-ref="searchInput" x-model="query" @input="performSearch()"
                                @keydown.enter.prevent="$event.shiftKey ? prevMatch() : nextMatch()" type="text"
                                placeholder="{{ __('Search in chat...') }}"
                                class="bg-transparent border-none outline-none text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 w-28 sm:w-44 md:w-56 focus:ring-0 p-0">

                            <!-- Match count badge -->
                            <template x-if="query.trim() !== ''">
                                <span
                                    class="text-[11px] font-semibold px-1.5 py-0.5 rounded bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 shrink-0 whitespace-nowrap"
                                    x-text="matches.length > 0 ? (currentIndex + 1) + ' of ' + matches.length : '0 results'">
                                </span>
                            </template>

                            <!-- Navigation between matches -->
                            <template x-if="matches.length > 0">
                                <div
                                    class="flex items-center gap-0.5 shrink-0 border-l border-zinc-300 dark:border-zinc-600 pl-1 ml-0.5">
                                    <button type="button" @click="prevMatch()"
                                        class="p-1 text-zinc-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded transition-colors cursor-pointer"
                                        title="{{ __('Previous match (Shift+Enter)') }}">
                                        <flux:icon name="chevron-up" class="w-3.5 h-3.5" />
                                    </button>
                                    <button type="button" @click="nextMatch()"
                                        class="p-1 text-zinc-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded transition-colors cursor-pointer"
                                        title="{{ __('Next match (Enter)') }}">
                                        <flux:icon name="chevron-down" class="w-3.5 h-3.5" />
                                    </button>
                                    <button type="button" @click="showDropdown = !showDropdown"
                                        :class="showDropdown ?
                                            'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-zinc-700' :
                                            'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'"
                                        class="p-1 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded transition-colors cursor-pointer"
                                        title="{{ __('View all matches') }}">
                                        <flux:icon name="bars-3-bottom-left" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </template>

                            <!-- Close search -->
                            <button type="button" @click="closeSearch()"
                                class="p-1 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors shrink-0 cursor-pointer"
                                title="{{ __('Close (Esc)') }}">
                                <flux:icon name="x-mark" class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Search trigger icon button -->
                        <button x-show="!open" type="button" @click="openSearch()"
                            class="p-2 text-zinc-400 hover:text-indigo-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                            title="{{ __('Search messages (Ctrl+F)') }}">
                            <flux:icon name="magnifying-glass" class="w-5 h-5" />
                        </button>

                        @if ($this->selectedGroup)
                            <button wire:click="$set('showManageGroupModal', true)"
                                class="p-2 text-zinc-400 hover:text-indigo-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                title="{{ __('Manage Members') }}">
                                <flux:icon name="user-group" class="w-5 h-5" />
                            </button>
                            @if ($this->isGroupAdmin())
                                <button wire:click="openRenameGroupModal({{ $this->selectedGroup->id }})"
                                    class="p-2 text-zinc-400 hover:text-indigo-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                    title="{{ __('Rename Group') }}">
                                    <flux:icon name="pencil-square" class="w-5 h-5" />
                                </button>
                            @endif
                        @endif

                        @if (($this->selectedGroup || $this->selectedUser) && count($messages) > 0)
                            <button wire:click="openClearChatModal"
                                class="p-2 text-zinc-400 hover:text-red-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                title="{{ __('Clear Chat') }}">
                                <flux:icon name="trash" class="w-5 h-5" />
                            </button>
                        @endif

                        <!-- Floating Search Results Dropdown -->
                        <div x-show="open && showDropdown && query.trim() !== '' && matches.length > 0"
                            @click.away="showDropdown = false" x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute top-full right-0 mt-2 w-80 sm:w-96 max-h-80 overflow-y-auto custom-scrollbar bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 p-2 z-50">
                            <div
                                class="px-2.5 py-1.5 text-xs font-semibold text-zinc-500 dark:text-zinc-400 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center">
                                <span x-text="matches.length + ' {{ __('matches found') }}'"></span>
                                <span class="text-[10px] text-zinc-400">{{ __('Click to jump') }}</span>
                            </div>
                            <div class="space-y-1 mt-1">
                                <template x-for="(item, idx) in matches" :key="item.id">
                                    <div @click="currentIndex = idx; jumpTo(item.id, true)"
                                        :class="currentIndex === idx ?
                                            'bg-indigo-50 dark:bg-indigo-950/40 border-indigo-200 dark:border-indigo-800/60' :
                                            'hover:bg-zinc-50 dark:hover:bg-zinc-800/60 border-transparent'"
                                        class="p-2.5 rounded-xl border transition-colors cursor-pointer text-left">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100"
                                                x-text="item.sender"></span>
                                            <span class="text-[10px] text-zinc-400" x-text="item.time"></span>
                                        </div>
                                        <div class="text-xs text-zinc-600 dark:text-zinc-300 line-clamp-2"
                                            x-html="formatSnippet(item.rawText, query)"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pinned Messages Banner -->
            @php
                $pinnedMessages = $messages ? $messages->where('is_pinned', true) : [];
            @endphp
            @if (($this->selectedUser || $this->selectedGroup) && count($pinnedMessages) > 0)
                <div x-data="{ expanded: false }"
                    class="bg-amber-50/90 dark:bg-amber-900/20 border-b border-amber-200/50 dark:border-amber-700/30 backdrop-blur-md px-4 py-2.5 z-20 shrink-0 shadow-sm flex flex-col">
                    <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
                        <div class="flex items-center gap-2 text-amber-700 dark:text-amber-500 font-medium text-xs">
                            <flux:icon name="bookmark" class="w-4 h-4" variant="solid" />
                            <span>{{ count($pinnedMessages) }}
                                {{ count($pinnedMessages) === 1 ? __('Pinned Message') : __('Pinned Messages') }}</span>
                        </div>
                        <flux:icon name="chevron-down"
                            class="w-4 h-4 text-amber-500 transition-transform duration-200"
                            x-bind:class="expanded ? 'rotate-180' : ''" />
                    </div>

                    <div x-show="expanded" x-collapse>
                        <div class="mt-3 space-y-2 max-h-40 overflow-y-auto pr-2 custom-scrollbar pb-1">
                            @foreach ($pinnedMessages as $pinned)
                                <div class="bg-white/60 dark:bg-zinc-800/60 rounded-lg p-2.5 text-sm border border-amber-100/50 dark:border-amber-700/20 hover:bg-white/90 dark:hover:bg-zinc-800/90 transition-colors flex gap-3 group relative cursor-pointer shadow-sm"
                                    @click="document.getElementById('message-{{ $pinned->id }}')?.scrollIntoView({behavior: 'smooth', block: 'center'});">
                                    <div class="shrink-0 flex flex-col items-center mt-0.5">
                                        <img src="{{ $pinned->sender->avatar ? Storage::url($pinned->sender->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($pinned->sender->name) . '&color=fff&background=6366f1' }}"
                                            class="w-6 h-6 rounded-full object-cover">
                                    </div>
                                    <div class="flex-1 min-w-0 pr-6">
                                        <div class="flex items-center justify-between">
                                            <span
                                                class="font-semibold text-zinc-700 dark:text-zinc-300 text-xs">{{ $pinned->sender->name }}</span>
                                            <span
                                                class="text-[10px] text-zinc-400">{{ $pinned->created_at->format('M j, g:i A') }}</span>
                                        </div>
                                        <div class="text-zinc-600 dark:text-zinc-400 text-xs truncate mt-0.5">
                                            {{ $pinned->message ?: __('Attachment') }}
                                        </div>
                                    </div>
                                    <button type="button" wire:click.stop="togglePinMessage({{ $pinned->id }})"
                                        class="absolute top-2 right-2 text-zinc-400 hover:text-amber-500 opacity-0 group-hover:opacity-100 transition-opacity"
                                        title="{{ __('Unpin') }}">
                                        <flux:icon name="bookmark" class="w-3.5 h-3.5" variant="solid" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Messages Area Container with WhatsApp-style Scroll Down Button -->
            <div class="relative flex-1 min-h-0 flex flex-col"
                x-data="chatMessagesScrollArea()"
                @scroll-bottom.window="scrollToBottom(true)">

                <div class="flex-1 p-4 md:p-6 overflow-y-auto space-y-4 md:space-y-6 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] dark:bg-[url('https://www.transparenttextures.com/patterns/stardust.png')] bg-zinc-50/50 dark:bg-zinc-900/90"
                    id="chat-messages"
                    x-ref="messagesContainer"
                    data-conversation="{{ $this->selectedGroup ? 'g' . $this->selectedGroup->id : ($this->selectedUser ? 'u' . $this->selectedUser->id : '') }}"
                    @scroll.passive="checkScroll()">

                @if (count($messages) === 0)
                    <div
                        class="h-full flex flex-col items-center justify-center text-zinc-400 dark:text-zinc-500 space-y-3">
                        <div
                            class="w-16 h-16 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-2">
                            <flux:icon name="chat-bubble-left-right" class="w-8 h-8 opacity-50" />
                        </div>
                        <p class="font-medium text-base">
                            {{ __('Say hello to :name!', ['name' => $this->selectedUser?->name ?? 'someone']) }}</p>
                        <p class="text-sm opacity-75">{{ __('When you send a message, it will appear here.') }}</p>
                    </div>
                @endif

                @php $lastDate = null; @endphp
                @foreach ($messages as $message)
                    @php
                        $messageDate = $message->created_at->isToday()
                            ? __('Today')
                            : ($message->created_at->isYesterday()
                                ? __('Yesterday')
                                : $message->created_at->format('F j, Y'));
                    @endphp

                    @if ($lastDate !== $messageDate)
                        <div class="flex justify-center my-2">
                            <span
                                class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border border-zinc-200 dark:border-zinc-700/80 text-zinc-500 dark:text-zinc-400 text-[11px] font-semibold px-3 py-1 rounded-full shadow-sm">
                                {{ $messageDate }}
                            </span>
                        </div>
                        @php $lastDate = $messageDate; @endphp
                    @endif
                    @if ($message->sender_id == auth()->id())
                        <div id="message-{{ $message->id }}" wire:key="msg-{{ $message->id }}"
                            data-searchable-message="true" data-message-id="{{ $message->id }}"
                            data-message-text="{{ htmlspecialchars($message->message ?? '', ENT_QUOTES, 'UTF-8') }}"
                            data-sender-name="{{ __('You') }}"
                            data-time="{{ $message->created_at->format('g:i A') }}" x-data="{ showEmojis: false }"
                            @close-other-pickers.window="if ($event.detail.except !== {{ $message->id }}) showEmojis = false"
                            class="flex flex-col items-end group relative transition-all duration-300"
                            :class="showEmojis ? 'z-40' : 'z-0'">

                            <!-- Message Bubble -->
                            <div
                                class="max-w-[75%] p-3 rounded-2xl rounded-tr-sm shadow-sm bg-indigo-600 dark:bg-indigo-500 text-white flex flex-col min-w-0 bubble-container transition-all duration-300 relative">
                                @if ($message->is_deleted)
                                    <div class="flex items-center gap-1.5 py-1 text-xs italic text-indigo-200">
                                        <flux:icon name="no-symbol" class="w-3.5 h-3.5 opacity-80" />
                                        <span>{{ __('This message was deleted.') }}</span>
                                    </div>
                                @else
                                    @if ($message->repliedMessage)
                                        <div onclick="highlightMessage({{ $message->reply_to_id }})"
                                            class="mb-2 p-2 rounded-lg bg-indigo-700/50 dark:bg-indigo-900/40 border-l-4 border-indigo-300 text-sm overflow-hidden opacity-90 cursor-pointer hover:opacity-100 transition-opacity">
                                            <div class="font-bold text-xs text-indigo-200 mb-0.5">
                                                {{ $message->repliedMessage->sender_id === auth()->id() ? __('You') : $message->repliedMessage->sender->name }}
                                            </div>
                                            <div class="truncate text-indigo-100/80 text-xs">
                                                {{ $message->repliedMessage->is_deleted ? __('This message was deleted.') : $message->repliedMessage->message }}
                                            </div>
                                        </div>
                                    @endif
                                    @if ($message->message)
                                        <div class="leading-relaxed text-[15px] break-words">
                                            {!! $formatMentions($message->message, true) !!}</div>
                                    @endif

                                    @if (!empty($message->attachments))
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($message->attachments as $attachment)
                                                @if (str_starts_with($attachment['type'], 'image/'))
                                                    <a href="{{ Storage::url($attachment['path']) }}" target="_blank"
                                                        class="block rounded-lg overflow-hidden border border-white/20 hover:opacity-90 transition-opacity">
                                                        <img src="{{ Storage::url($attachment['path']) }}"
                                                            class="max-w-full sm:max-w-[200px] max-h-[200px] object-cover">
                                                    </a>
                                                @else
                                                    <a href="{{ Storage::url($attachment['path']) }}" target="_blank"
                                                        class="flex items-center gap-2 p-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors text-sm border border-white/20">
                                                        <flux:icon name="document-text" class="w-5 h-5" />
                                                        <span
                                                            class="truncate max-w-[150px]">{{ $attachment['name'] }}</span>
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                @endif

                                <div
                                    class="flex items-center justify-end gap-1.5 mt-1 -mb-1 text-indigo-100/90 whitespace-nowrap">
                                    @if ($message->is_pinned)
                                        <flux:icon name="bookmark" class="w-3 h-3 text-amber-300" variant="solid" />
                                    @endif
                                    @if ($message->is_edited)
                                        <span class="text-[10px] italic">{{ __('Edited') }}</span>
                                    @endif
                                    <span class="text-[10px]">{{ $message->created_at->format('g:i A') }}</span>
                                    @if ($message->is_read)
                                        <!-- Read (Double check blue/light) -->
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="w-3.5 h-3.5 text-blue-300 drop-shadow-[0_1px_1px_rgba(0,0,0,0.1)]"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6 7 17l-5-5"></path>
                                            <path d="m22 10-7.5 7.5L13 16"></path>
                                        </svg>
                                    @else
                                        @if ($this->selectedUser)
                                            <!-- Delivered (Double check gray) -->
                                            <svg x-cloak x-show="onlineUsers.includes({{ $this->selectedUser->id }})"
                                                xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M18 6 7 17l-5-5"></path>
                                                <path d="m22 10-7.5 7.5L13 16"></path>
                                            </svg>
                                            <!-- Sent (Single check gray) -->
                                            <svg x-cloak
                                                x-show="!onlineUsers.includes({{ $this->selectedUser->id }})"
                                                xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 6 9 17l-5-5"></path>
                                            </svg>
                                        @else
                                            <!-- Group Sent (Single check gray) -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 6 9 17l-5-5"></path>
                                            </svg>
                                        @endif
                                    @endif
                                </div>
                                @if (!empty($message->reactions))
                                    <div
                                        class="absolute -bottom-4 right-3 flex items-center gap-1 bg-white/95 dark:bg-zinc-800/95 backdrop-blur-sm rounded-full px-2 py-0.5 shadow-md border border-zinc-200/90 dark:border-zinc-700/80 z-10 text-xs select-none">
                                        @foreach (array_count_values($message->reactions) as $emoji => $count)
                                            <button type="button"
                                                wire:click="toggleReaction({{ $message->id }}, '{{ addslashes($emoji) }}')"
                                                class="flex items-center gap-1 hover:scale-110 active:scale-95 transition-transform cursor-pointer {{ $loop->first ? 'ml-0.5' : '' }} {{ $loop->last ? 'mr-0.5' : '' }}"
                                                title="{{ __('Click to react') }}">
                                                <span
                                                    class="text-base sm:text-[18px] leading-none">{{ $emoji }}</span>
                                                @if ($count > 1)
                                                    <span
                                                        class="text-zinc-600 dark:text-zinc-300 font-semibold text-xs leading-none">{{ $count }}</span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Action Menu (below message) -->
                            <div class="transition-opacity flex items-center gap-1 {{ !empty($message->reactions) ? 'mt-4' : 'mt-1' }} relative"
                                :class="showEmojis ? 'opacity-100 z-50' : 'opacity-0 group-hover:opacity-100'">
                                <!-- Invisible Overlay to close picker -->
                                <div x-show="showEmojis" @click="showEmojis = false" class="fixed inset-0 z-40">
                                </div>

                                @if (!$message->is_deleted)
                                    <div class="relative">
                                        <button
                                            @click="
                                                if (!showEmojis) $dispatch('close-other-pickers', { except: {{ $message->id }} });
                                                showEmojis = !showEmojis;
                                                if (showEmojis) {
                                                    $nextTick(() => {
                                                        $refs.pickerWrap?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                                    });
                                                }
                                            "
                                            class="p-1.5 text-zinc-400 hover:text-yellow-500 dark:hover:text-yellow-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer relative z-50"
                                            title="{{ __('React') }}">
                                            <flux:icon name="face-smile" class="w-3.5 h-3.5" />
                                        </button>
                                        <!-- Emoji Picker: displayed below the icon -->
                                        <div x-show="showEmojis" x-cloak x-transition x-ref="pickerWrap"
                                            class="absolute right-0 top-full mt-2 z-50 shadow-2xl rounded-xl border border-zinc-200 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 flex overflow-hidden max-w-[calc(100vw-2rem)]"
                                            style="max-width: 350px;">
                                            <emoji-picker wire:ignore
                                                @emoji-click="$wire.toggleReaction({{ $message->id }}, $event.detail.unicode); showEmojis = false"
                                                style="width: 100%;"></emoji-picker>
                                        </div>
                                    </div>

                                    <button
                                        @click="navigator.clipboard.writeText(`{{ addslashes($message->message) }}`); showEmojis = false;"
                                        class="p-1.5 text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                        title="{{ __('Copy') }}">
                                        <flux:icon name="clipboard-document" class="w-3.5 h-3.5" />
                                    </button>
                                    <button wire:click="togglePinMessage({{ $message->id }})"
                                        class="p-1.5 {{ $message->is_pinned ? 'text-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'text-zinc-400 hover:text-amber-500 hover:bg-zinc-100 dark:hover:bg-zinc-800' }} rounded-full transition-colors cursor-pointer"
                                        title="{{ $message->is_pinned ? __('Unpin') : __('Pin') }}">
                                        <flux:icon name="bookmark" class="w-3.5 h-3.5"
                                            variant="{{ $message->is_pinned ? 'solid' : 'outline' }}" />
                                    </button>
                                    <button wire:click="startForwardMessage({{ $message->id }})"
                                        class="p-1.5 text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                        title="{{ __('Forward') }}">
                                        <flux:icon name="arrow-right-circle" class="w-3.5 h-3.5" />
                                    </button>
                                    <button wire:click="startReplyMessage({{ $message->id }})"
                                        class="p-1.5 text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                        title="{{ __('Reply') }}">
                                        <flux:icon name="arrow-uturn-left" class="w-3.5 h-3.5" />
                                    </button>
                                    <button wire:click="startEditMessage({{ $message->id }})"
                                        class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                        title="{{ __('Edit') }}">
                                        <flux:icon name="pencil" class="w-3.5 h-3.5" />
                                    </button>
                                @endif
                                <button wire:click="openDeleteMessageModal({{ $message->id }})"
                                    class="p-1.5 text-zinc-400 hover:text-red-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                    title="{{ __('Delete') }}">
                                    <flux:icon name="trash" class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    @else
                        <div id="message-{{ $message->id }}" wire:key="msg-{{ $message->id }}"
                            data-searchable-message="true" data-message-id="{{ $message->id }}"
                            data-message-text="{{ htmlspecialchars($message->message ?? '', ENT_QUOTES, 'UTF-8') }}"
                            data-sender-name="{{ htmlspecialchars($message->sender?->name ?? __('User'), ENT_QUOTES, 'UTF-8') }}"
                            data-time="{{ $message->created_at->format('g:i A') }}" x-data="{ showEmojis: false }"
                            @close-other-pickers.window="if ($event.detail.except !== {{ $message->id }}) showEmojis = false"
                            class="flex items-start gap-2 group relative transition-all duration-300"
                            :class="showEmojis ? 'z-40' : 'z-0'">

                            <flux:avatar size="xs" :name="$message->sender->name"
                                :src="$message->sender->avatar" class="mt-1 shrink-0" />

                            <div class="flex flex-col items-start max-w-[75%] min-w-0">
                                @if ($this->selectedGroup)
                                    <span
                                        class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 mb-1 ml-1 leading-none">
                                        {{ $message->sender->name }}
                                    </span>
                                @endif

                                <!-- Message Bubble -->
                                <div
                                    class="max-w-full p-3 rounded-2xl rounded-tl-sm shadow-sm bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 border border-zinc-100 dark:border-zinc-700/80 flex flex-col min-w-0 bubble-container transition-all duration-300 relative">
                                    @if ($message->is_deleted)
                                        <div
                                            class="flex items-center gap-1.5 py-1 text-xs italic text-zinc-400 dark:text-zinc-500">
                                            <flux:icon name="no-symbol" class="w-3.5 h-3.5 opacity-80" />
                                            <span>{{ __('This message was deleted.') }}</span>
                                        </div>
                                    @else
                                        @if ($message->repliedMessage)
                                            <div onclick="highlightMessage({{ $message->reply_to_id }})"
                                                class="mb-2 p-2 rounded-lg bg-zinc-100 dark:bg-zinc-700/50 border-l-4 border-indigo-500 text-sm overflow-hidden opacity-90 cursor-pointer hover:opacity-100 transition-opacity">
                                                <div
                                                    class="font-bold text-xs text-indigo-600 dark:text-indigo-400 mb-0.5">
                                                    {{ $message->repliedMessage->sender_id === auth()->id() ? __('You') : $message->repliedMessage->sender->name }}
                                                </div>
                                                <div class="truncate text-zinc-600 dark:text-zinc-400 text-xs">
                                                    {{ $message->repliedMessage->is_deleted ? __('This message was deleted.') : $message->repliedMessage->message }}
                                                </div>
                                            </div>
                                        @endif
                                        @if ($message->message)
                                            <div class="leading-relaxed text-[15px] break-words">
                                                {!! $formatMentions($message->message, false) !!}</div>
                                        @endif

                                        @if (!empty($message->attachments))
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach ($message->attachments as $attachment)
                                                    @if (str_starts_with($attachment['type'], 'image/'))
                                                        <a href="{{ Storage::url($attachment['path']) }}"
                                                            target="_blank"
                                                            class="block rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 hover:opacity-90 transition-opacity">
                                                            <img src="{{ Storage::url($attachment['path']) }}"
                                                                class="max-w-full sm:max-w-[200px] max-h-[200px] object-cover">
                                                        </a>
                                                    @else
                                                        <a href="{{ Storage::url($attachment['path']) }}"
                                                            target="_blank"
                                                            class="flex items-center gap-2 p-2 rounded-lg bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors text-sm border border-zinc-200 dark:border-zinc-700">
                                                            <flux:icon name="document-text"
                                                                class="w-5 h-5 text-indigo-500" />
                                                            <span
                                                                class="truncate max-w-[150px]">{{ $attachment['name'] }}</span>
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif

                                    <div
                                        class="flex items-center justify-end gap-1.5 mt-1 -mb-1 text-zinc-400 dark:text-zinc-500 whitespace-nowrap">
                                        @if ($message->is_pinned)
                                            <flux:icon name="bookmark" class="w-3 h-3 text-amber-500"
                                                variant="solid" />
                                        @endif
                                        @if ($message->is_edited)
                                            <span class="text-[10px] italic">{{ __('Edited') }}</span>
                                        @endif
                                        <span class="text-[10px]">{{ $message->created_at->format('g:i A') }}</span>
                                    </div>
                                    @if (!empty($message->reactions))
                                        <div
                                            class="absolute -bottom-4 left-3 flex items-center gap-1 bg-white/95 dark:bg-zinc-800/95 backdrop-blur-sm rounded-full px-2 py-0.5 shadow-md border border-zinc-200/90 dark:border-zinc-700/80 z-10 text-xs select-none">
                                            @foreach (array_count_values($message->reactions) as $emoji => $count)
                                                <button type="button"
                                                    wire:click="toggleReaction({{ $message->id }}, '{{ addslashes($emoji) }}')"
                                                    class="flex items-center gap-1 hover:scale-110 active:scale-95 transition-transform cursor-pointer {{ $loop->first ? 'ml-0.5' : '' }} {{ $loop->last ? 'mr-0.5' : '' }}"
                                                    title="{{ __('Click to react') }}">
                                                    <span
                                                        class="text-base sm:text-[18px] leading-none">{{ $emoji }}</span>
                                                    @if ($count > 1)
                                                        <span
                                                            class="text-zinc-600 dark:text-zinc-300 font-semibold text-xs leading-none">{{ $count }}</span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- Action Menu (below message) -->
                                <div class="transition-opacity flex items-center gap-1 {{ !empty($message->reactions) ? 'mt-4' : 'mt-1' }} relative"
                                    :class="showEmojis ? 'opacity-100 z-50' : 'opacity-0 group-hover:opacity-100'">
                                    <!-- Invisible Overlay to close picker -->
                                    <div x-show="showEmojis" @click="showEmojis = false" class="fixed inset-0 z-40">
                                    </div>

                                    @if (!$message->is_deleted)
                                        <div class="relative">
                                            <button
                                                @click="
                                                    if (!showEmojis) $dispatch('close-other-pickers', { except: {{ $message->id }} });
                                                    showEmojis = !showEmojis;
                                                    if (showEmojis) {
                                                        $nextTick(() => {
                                                            $refs.pickerWrap?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                                        });
                                                    }
                                                "
                                                class="p-1.5 text-zinc-400 hover:text-yellow-500 dark:hover:text-yellow-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer relative z-50"
                                                title="{{ __('React') }}">
                                                <flux:icon name="face-smile" class="w-3.5 h-3.5" />
                                            </button>
                                            <!-- Emoji Picker: displayed below the icon -->
                                            <div x-show="showEmojis" x-cloak x-transition x-ref="pickerWrap"
                                                class="absolute left-0 top-full mt-2 z-50 shadow-2xl rounded-xl border border-zinc-200 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 flex overflow-hidden max-w-[calc(100vw-2rem)]"
                                                style="max-width: 350px;">
                                                <emoji-picker wire:ignore
                                                    @emoji-click="$wire.toggleReaction({{ $message->id }}, $event.detail.unicode); showEmojis = false"
                                                    style="width: 100%;"></emoji-picker>
                                            </div>
                                        </div>
                                    @endif

                                    @if (!$message->is_deleted)
                                        <button
                                            @click="navigator.clipboard.writeText(`{{ addslashes($message->message) }}`); showEmojis = false;"
                                            class="p-1.5 text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                            title="{{ __('Copy') }}">
                                            <flux:icon name="clipboard-document" class="w-3.5 h-3.5" />
                                        </button>
                                        <button wire:click="togglePinMessage({{ $message->id }})"
                                            class="p-1.5 {{ $message->is_pinned ? 'text-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'text-zinc-400 hover:text-amber-500 hover:bg-zinc-100 dark:hover:bg-zinc-800' }} rounded-full transition-colors cursor-pointer"
                                            title="{{ $message->is_pinned ? __('Unpin') : __('Pin') }}">
                                            <flux:icon name="bookmark" class="w-3.5 h-3.5"
                                                variant="{{ $message->is_pinned ? 'solid' : 'outline' }}" />
                                        </button>
                                        <button wire:click="startForwardMessage({{ $message->id }})"
                                            class="p-1.5 text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                            title="{{ __('Forward') }}">
                                            <flux:icon name="arrow-right-circle" class="w-3.5 h-3.5" />
                                        </button>
                                        <button wire:click="startReplyMessage({{ $message->id }})"
                                            class="p-1.5 text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                            title="{{ __('Reply') }}">
                                            <flux:icon name="arrow-uturn-left" class="w-3.5 h-3.5" />
                                        </button>
                                    @endif
                                    <button wire:click="openDeleteMessageModal({{ $message->id }})"
                                        class="p-1.5 text-zinc-400 hover:text-red-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-full transition-colors cursor-pointer"
                                        title="{{ __('Delete') }}">
                                        <flux:icon name="trash" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
                </div>

                <!-- Floating Scroll Down (WhatsApp-style) Button -->
                <div class="absolute bottom-4 inset-x-0 flex justify-center pointer-events-none z-20">
                    <button type="button"
                        id="scroll-to-bottom-button"
                        x-show="showScrollDown"
                        x-cloak
                        x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-200"
                        x-transition:enter-start="opacity-0 translate-y-3 scale-75"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-3 scale-75"
                        @click="scrollToBottom(true)"
                        class="pointer-events-auto relative w-10 h-10 md:w-11 md:h-11 rounded-full bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-200 shadow-lg border border-zinc-200/90 dark:border-zinc-700/90 hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:text-indigo-600 dark:hover:text-indigo-400 active:scale-95 transition-all duration-150 flex items-center justify-center cursor-pointer group"
                        title="{{ __('Scroll to bottom') }}"
                        aria-label="{{ __('Scroll to bottom') }}">
                        <flux:icon name="chevron-down" class="w-5 h-5 shrink-0 transition-transform group-hover:translate-y-0.5" />

                        <!-- WhatsApp-style New Messages Count Badge -->
                        <span x-show="unreadBelow > 0" x-cloak
                            class="absolute -top-1.5 -right-1 bg-emerald-500 text-white text-[10px] font-bold min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center shadow-md animate-pulse select-none"
                            x-text="unreadBelow > 99 ? '99+' : unreadBelow">
                        </span>
                    </button>
                </div>
            </div>

            <!-- Typing Indicator -->
            <div x-data="{ typing: '' }" x-init="window.Echo.private('chat.{{ $loginId }}')
                .listen('UserTyping', (event) => {
                    typing = event.userName + ' is typing...';
                    setTimeout(() => { typing = '' }, 2000);
                });"
                class="absolute bottom-[80px] left-0 px-8 py-3 bg-gradient-to-t from-white via-white/90 to-transparent dark:from-zinc-900 dark:via-zinc-900/90 w-full pointer-events-none z-20"
                x-show="typing !== ''" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
                style="display: none;">
                <div
                    class="text-xs font-semibold text-indigo-500 dark:text-indigo-400 flex items-center gap-2 drop-shadow-sm">
                    <span class="flex gap-1">
                        <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce"
                            style="animation-delay: 0ms"></span>
                        <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce"
                            style="animation-delay: 150ms"></span>
                        <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce"
                            style="animation-delay: 300ms"></span>
                    </span>
                    <span x-text="typing" class="italic tracking-wide"></span>
                </div>
            </div>

            <!-- Input Area -->
            <div class="relative z-30">
                @if ($editingMessageId)
                    <div
                        class="absolute bottom-full left-0 right-0 bg-indigo-50/95 dark:bg-indigo-900/90 px-5 py-2.5 text-xs text-indigo-700 dark:text-indigo-300 flex justify-between items-center border-t border-indigo-100 dark:border-indigo-800/50 backdrop-blur-md">
                        <span class="font-medium flex items-center gap-1.5">
                            <flux:icon name="pencil" class="w-3.5 h-3.5" />
                            {{ __('Editing Message') }}
                        </span>
                        <button type="button" wire:click="cancelEdit"
                            class="text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-200 transition-colors">
                            <flux:icon name="x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                @elseif ($replyingToMessageId)
                    <div
                        class="absolute bottom-full left-0 right-0 bg-zinc-100/95 dark:bg-zinc-800/95 px-5 py-2.5 text-xs text-zinc-700 dark:text-zinc-300 flex justify-between items-center border-t border-zinc-200 dark:border-zinc-700/50 backdrop-blur-md">
                        <span class="flex items-center gap-2 overflow-hidden">
                            <flux:icon name="arrow-uturn-left" class="w-3.5 h-3.5 shrink-0" />
                            <span class="font-medium shrink-0">{{ __('Replying to') }}
                                {{ $replyingToMessageSender }}:</span>
                            <span class="truncate opacity-80">{{ $replyingToMessageText }}</span>
                        </span>
                        <button type="button" wire:click="cancelReply"
                            class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors shrink-0 ml-2">
                            <flux:icon name="x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                @endif

                <!-- Attachments Preview -->
                @if (count($attachments) > 0)
                    <div
                        class="bg-zinc-50/95 dark:bg-zinc-900/95 border-t border-zinc-200 dark:border-zinc-700/80 p-3 px-4 flex flex-wrap gap-2 max-h-32 overflow-y-auto backdrop-blur-md">
                        @foreach ($attachments as $index => $attachment)
                            <div
                                class="relative group rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden bg-white dark:bg-zinc-800 w-16 h-16 shrink-0 flex items-center justify-center">
                                @if (in_array(strtolower($attachment->extension()), ['jpg', 'jpeg', 'png', 'webp']))
                                    <img src="{{ $attachment->temporaryUrl() }}" class="w-full h-full object-cover">
                                @else
                                    <div class="flex flex-col items-center justify-center text-zinc-500">
                                        <flux:icon name="document-text" class="w-6 h-6" />
                                        <span
                                            class="text-[9px] uppercase font-bold mt-0.5">{{ $attachment->extension() }}</span>
                                    </div>
                                @endif
                                <button type="button" wire:click="removeAttachment({{ $index }})"
                                    class="absolute top-1 right-1 bg-black/60 hover:bg-red-500 text-white rounded-full p-0.5 opacity-0 md:opacity-0 group-hover:opacity-100 transition-opacity">
                                    <flux:icon name="x-mark" class="w-3 h-3" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Upload Loading State -->
                <div wire:loading wire:target="attachments"
                    class="w-full bg-zinc-50/95 dark:bg-zinc-900/95 border-t border-zinc-200 dark:border-zinc-700/80 p-3 px-4 flex items-center gap-3 backdrop-blur-md text-zinc-500 text-sm">
                    <flux:icon name="arrow-path" class="w-4 h-4 animate-spin" />
                    {{ __('Uploading attachments...') }}
                </div>

                <form wire:submit="submit" x-data="tagComposer(@js($taggableUsers), {{ count($attachments) > 0 ? 'true' : 'false' }})"
                    x-effect="users = @js($taggableUsers); hasAttachments = {{ count($attachments) > 0 ? 'true' : 'false' }}"
                    @submit="if (!canSend()) { $event.preventDefault(); return false; }"
                    class="p-3 md:p-4 border-t border-zinc-200 dark:border-zinc-700/80 bg-zinc-50/80 dark:bg-zinc-900/80 backdrop-blur-md flex items-end gap-2 md:gap-3 relative">

                    <!-- Composer Emoji Picker Button -->
                    <div x-data="{ showComposerEmojis: false }" class="relative shrink-0">
                        <!-- Invisible overlay to close picker -->
                        <div x-show="showComposerEmojis" @click="showComposerEmojis = false"
                            class="fixed inset-0 z-40"></div>

                        <button type="button" @click="showComposerEmojis = !showComposerEmojis"
                            class="p-3 md:p-3.5 text-zinc-400 hover:text-yellow-500 dark:hover:text-yellow-400 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 rounded-2xl transition-all shadow-sm flex items-center justify-center relative z-50 cursor-pointer"
                            title="{{ __('Emoji') }}">
                            <flux:icon name="face-smile" class="w-6 h-6" />
                        </button>

                        <!-- Emoji Picker -->
                        <div x-show="showComposerEmojis" x-cloak x-transition
                            class="absolute bottom-full left-0 mb-3 z-50 shadow-2xl rounded-xl border border-zinc-200 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 flex overflow-hidden">
                            <emoji-picker wire:ignore
                                @emoji-click="
                                const input = document.getElementById('message-input');
                                const start = input.selectionStart;
                                const end = input.selectionEnd;
                                const text = input.value;
                                input.value = text.substring(0, start) + $event.detail.unicode + text.substring(end);
                                input.selectionStart = input.selectionEnd = start + $event.detail.unicode.length;
                                input.dispatchEvent(new Event('input', { bubbles: true }));
                             "></emoji-picker>
                        </div>
                    </div>

                    <!-- Tag / Mention Button -->
                    {{-- <button type="button" @mousedown.prevent @click="triggerTag()"
                        class="p-3 md:p-3.5 text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 rounded-2xl transition-all shadow-sm flex items-center justify-center font-bold text-base leading-none shrink-0 cursor-pointer"
                        title="{{ __('Tag someone (@)') }}">
                        @
                    </button> --}}

                    <!-- Attachment Button -->
                    <div class="relative shrink-0 flex items-center justify-center">
                        <input type="file" wire:model="attachments" multiple class="hidden" id="attachment-input"
                            accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                        <label for="attachment-input"
                            class="p-3 md:p-3.5 text-zinc-400 hover:text-indigo-500 dark:hover:text-indigo-400 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 rounded-2xl transition-all shadow-sm flex items-center justify-center cursor-pointer mb-0">
                            <flux:icon name="paper-clip" class="w-6 h-6" />
                        </label>
                    </div>

                    <!-- Textarea & Tag Popover Container -->
                    <div class="flex-1 relative" @click.away="showTagList = false">
                        <!-- Tag / Mention Popover -->
                        <div x-show="showTagList && filteredUsers().length > 0" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                            class="absolute bottom-full left-0 mb-3 w-72 sm:w-80 max-h-64 overflow-y-auto custom-scrollbar bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 p-2 z-50">

                            <div
                                class="px-2.5 py-1.5 text-[11px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 mb-1">
                                <span>{{ __('Tag a member') }}</span>
                                <span
                                    class="text-[10px] font-normal lowercase tracking-normal">{{ __('↑↓ navigate • enter select') }}</span>
                            </div>

                            <div class="space-y-1">
                                <template x-for="(user, index) in filteredUsers()" :key="user.id">
                                    <button type="button" @mousedown.prevent @click="selectUser(user)"
                                        @mouseenter="selectedIndex = index"
                                        :class="selectedIndex === index ?
                                            'bg-indigo-50 dark:bg-indigo-950/60 border-indigo-200 dark:border-indigo-800/60' :
                                            'hover:bg-zinc-50 dark:hover:bg-zinc-800/60 border-transparent'"
                                        class="w-full flex items-center gap-3 p-2 rounded-xl transition-all border text-left cursor-pointer">
                                        <div class="w-8 h-8 rounded-full shrink-0 flex items-center justify-center font-bold text-xs text-white shadow-sm overflow-hidden relative"
                                            :style="'background-color: ' + (user.color || '#6366f1')">
                                            <span class="select-none tracking-tight text-[11px]"
                                                x-text="user.initials"></span>
                                            <template x-if="user.avatar">
                                                <img :src="user.avatar"
                                                    class="absolute inset-0 w-full h-full object-cover"
                                                    :alt="user.name" x-on:error="$el.remove()">
                                            </template>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate"
                                                    x-text="user.name"></span>
                                                <span class="text-[10px] text-indigo-500 font-medium ml-1">@<span
                                                        x-text="user.name"></span></span>
                                            </div>
                                            <p class="text-[10px] text-zinc-400 truncate" x-text="user.email || ''">
                                            </p>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Textarea -->
                        <textarea id="message-input" wire:model="newMessage" x-data="{
                            resize() {
                                $el.style.height = 'auto';
                                $el.style.height = Math.min($el.scrollHeight, 120) + 'px';
                            }
                        }" x-init="setTimeout(() => resize(), 0);
                        $watch('$wire.newMessage', val => { if (!val) setTimeout(() => resize(), 0) })"
                            @input="messageText = $el.value; resize(); handleInput($event)" @keydown="handleKeyDown($event)" rows="1"
                            placeholder="{{ __('Type a message... (use @ to tag)') }}"
                            class="w-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 rounded-2xl px-4 md:px-5 py-3 md:py-3.5 text-[15px] text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 outline-none transition-all shadow-sm hover:border-zinc-300 dark:hover:border-zinc-600 resize-none overflow-y-auto block"></textarea>
                    </div>

                    <button type="submit"
                        :disabled="!canSend()"
                        wire:loading.attr="disabled" wire:target="attachments,submit"
                        :class="canSend() ? 'bg-indigo-600 hover:bg-indigo-700 text-white hover:scale-105 active:scale-95 cursor-pointer shadow-sm hover:shadow-indigo-500/25' : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-600 cursor-not-allowed opacity-60 pointer-events-none shadow-none'"
                        class="p-3 md:p-3.5 rounded-2xl transition-all duration-200 transform flex items-center justify-center shrink-0 group"
                        :title="canSend() ? '{{ __('Send message') }}' : '{{ __('Type a message or add an attachment to send') }}'">
                        <flux:icon name="paper-airplane"
                            class="w-5 h-5 -mt-0.5 ml-0.5 transition-transform"
                            x-bind:class="canSend() ? 'group-hover:translate-x-0.5 group-hover:-translate-y-0.5' : ''" />
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- Create Group Modal -->
    <div x-data="{ open: @entangle('showCreateGroupModal') }" x-show="open"
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-zinc-900/50 backdrop-blur-sm"
        x-cloak>
        <div @click.away="open = false"
            class="relative w-full max-w-md p-4 bg-white dark:bg-zinc-900 rounded-2xl shadow-xl m-4 border border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Create New Group') }}</h3>
                <button @click="open = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <flux:icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <form wire:submit="saveNewGroup" class="space-y-4">
                <div>
                    <label
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Group Name') }}</label>
                    <input type="text" wire:model="newGroupName" required
                        class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all dark:text-white"
                        placeholder="Awesome Team">
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Select Members') }}</label>
                    <div
                        class="max-h-48 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 p-2 space-y-1 custom-scrollbar">
                        @foreach ($users as $u)
                            <label
                                class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-zinc-700 rounded-lg cursor-pointer transition-colors">
                                <input type="checkbox" wire:model="newGroupMembers" value="{{ $u->id }}"
                                    class="rounded text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-600">
                                <flux:avatar size="xs" :name="$u->name" :src="$u->avatar" />
                                <span
                                    class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $u->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="open = false"
                        class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition-colors">
                        {{ __('Create Group') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rename Group Modal -->
    <div x-data="{ open: @entangle('showRenameGroupModal') }" x-show="open"
        class="fixed inset-0 z-[110] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-zinc-900/50 backdrop-blur-sm"
        x-cloak>
        <div @click.away="open = false"
            class="relative w-full max-w-md p-5 bg-white dark:bg-zinc-900 rounded-2xl shadow-xl m-4 border border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <flux:icon name="pencil-square" class="w-5 h-5" />
                    </div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ __('Rename Group') }}</h3>
                </div>
                <button type="button" @click="open = false"
                    class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 p-1 rounded-lg">
                    <flux:icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <form wire:submit="renameGroup" class="space-y-4">
                <div>
                    <label
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('Group Name') }}</label>
                    <input type="text" wire:model="editGroupName" required autofocus
                        class="w-full bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm outline-none transition-all dark:text-white"
                        placeholder="{{ __('Enter new group name') }}">
                    @error('editGroupName')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <button type="button" @click="open = false"
                        class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                        <flux:icon name="check" class="w-4 h-4" />
                        <span>{{ __('Save') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage Group Modal -->
    @if ($this->selectedGroup)
        <div x-data="{ open: @entangle('showManageGroupModal'), tab: 'members', searchMember: '', searchAdd: '' }" x-show="open" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-zinc-900/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.away="open = false"
                class="relative w-full max-w-lg p-5 bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl m-4 border border-zinc-200 dark:border-zinc-800"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <flux:icon name="user-group" class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                <span>{{ $this->selectedGroup->name }}</span>
                                @if ($this->isGroupAdmin())
                                    <button type="button"
                                        wire:click="openRenameGroupModal({{ $this->selectedGroup->id }})"
                                        class="text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors p-1 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer"
                                        title="{{ __('Rename Group') }}">
                                        <flux:icon name="pencil-square" class="w-4 h-4" />
                                    </button>
                                @endif
                            </h3>
                            <p class="text-xs text-zinc-500 font-medium">
                                {{ trans_choice('{1} :count member|[2,*] :count members', $this->selectedGroup->users->count(), ['count' => $this->selectedGroup->users->count()]) }}
                            </p>
                        </div>
                    </div>
                    <button @click="open = false"
                        class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer">
                        <flux:icon name="x-mark" class="w-5 h-5" />
                    </button>
                </div>

                @php
                    $isGroupAdmin = $this->isGroupAdmin();
                    $currentMembers = $this->selectedGroup->users;
                    $currentMemberIds = $currentMembers->pluck('id');
                    $availableUsers = $users->reject(fn($u) => $currentMemberIds->contains($u->id));
                @endphp

                <!-- Tabs (Members & Add User) -->
                <div class="flex gap-2 mt-4 p-1 bg-zinc-100 dark:bg-zinc-800/60 rounded-xl">
                    <button type="button" @click="tab = 'members'"
                        :class="tab === 'members' ?
                            'bg-white dark:bg-zinc-800 text-indigo-600 dark:text-indigo-400 shadow-sm font-semibold' :
                            'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 font-medium'"
                        class="flex-1 py-1.5 px-3 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <flux:icon name="users" class="w-3.5 h-3.5" />
                        <span>{{ __('Current Members') }}</span>
                        <span
                            class="px-1.5 py-0.5 text-[10px] rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                            {{ $currentMembers->count() }}
                        </span>
                    </button>

                    @if ($isGroupAdmin)
                        <button type="button" @click="tab = 'add'"
                            :class="tab === 'add' ?
                                'bg-white dark:bg-zinc-800 text-indigo-600 dark:text-indigo-400 shadow-sm font-semibold' :
                                'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 font-medium'"
                            class="flex-1 py-1.5 px-3 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                            <flux:icon name="user-plus" class="w-3.5 h-3.5" />
                            <span>{{ __('Add Members') }}</span>
                            @if ($availableUsers->count() > 0)
                                <span
                                    class="px-1.5 py-0.5 text-[10px] rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300">
                                    {{ $availableUsers->count() }}
                                </span>
                            @endif
                        </button>
                    @endif
                </div>

                <!-- Tab 1: Current Members List -->
                <div x-show="tab === 'members'" class="mt-4 space-y-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <flux:icon name="magnifying-glass" class="w-4 h-4 text-zinc-400" />
                        </div>
                        <input x-model="searchMember" type="text"
                            placeholder="{{ __('Search current members...') }}"
                            class="w-full bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-9 pr-4 py-2 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 outline-none">
                    </div>

                    <div class="max-h-72 overflow-y-auto custom-scrollbar space-y-1.5 pr-1">
                        @foreach ($currentMembers as $member)
                            <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border border-transparent hover:border-zinc-100 dark:hover:border-zinc-800"
                                x-show="searchMember === '' || '{{ strtolower($member->name) }}'.includes(searchMember.toLowerCase())">
                                <div class="flex items-center gap-3 min-w-0">
                                    <flux:avatar size="sm" :name="$member->name" :src="$member->avatar" />
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                                {{ $member->name }}
                                            </span>
                                            @if ($member->id === auth()->id())
                                                <span
                                                    class="px-1.5 py-0.5 text-[10px] font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 rounded-md">
                                                    {{ __('You') }}
                                                </span>
                                            @endif
                                            @if ($member->id === $this->selectedGroup->owner_id)
                                                <span
                                                    class="px-1.5 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 rounded-md flex items-center gap-0.5">
                                                    <flux:icon name="star" class="w-2.5 h-2.5" variant="solid" />
                                                    {{ __('Owner') }}
                                                </span>
                                            @elseif ($member->pivot->is_admin)
                                                <span
                                                    class="px-1.5 py-0.5 text-[10px] font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 rounded-md">
                                                    {{ __('Admin') }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-zinc-400 truncate">{{ $member->email }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    @if ($isGroupAdmin && $member->id !== $this->selectedGroup->owner_id && $member->id !== auth()->id())
                                        <button type="button"
                                            wire:confirm="{{ __('Are you sure you want to remove :name from this group?', ['name' => $member->name]) }}"
                                            wire:click="removeUserFromGroup({{ $member->id }})"
                                            class="px-2.5 py-1 text-xs font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/40 dark:hover:text-red-400 rounded-lg transition-colors flex items-center gap-1 cursor-pointer"
                                            title="{{ __('Remove from group') }}">
                                            <flux:icon name="user-minus" class="w-3.5 h-3.5" />
                                            <span>{{ __('Remove') }}</span>
                                        </button>
                                    @elseif ($member->id === auth()->id() && $member->id !== $this->selectedGroup->owner_id)
                                        <button type="button"
                                            wire:click="openLeaveGroupModal"
                                            class="px-2.5 py-1 text-xs font-medium text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-950/40 dark:hover:text-amber-400 rounded-lg transition-colors flex items-center gap-1 cursor-pointer"
                                            title="{{ __('Leave group') }}">
                                            <flux:icon name="arrow-right-start-on-rectangle" class="w-3.5 h-3.5" />
                                            <span>{{ __('Leave') }}</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Tab 2: Add Members List (Admin Only) -->
                @if ($isGroupAdmin)
                    <div x-show="tab === 'add'" class="mt-4 space-y-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <flux:icon name="magnifying-glass" class="w-4 h-4 text-zinc-400" />
                            </div>
                            <input x-model="searchAdd" type="text"
                                placeholder="{{ __('Search users to add...') }}"
                                class="w-full bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-9 pr-4 py-2 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 outline-none">
                        </div>

                        <div class="max-h-72 overflow-y-auto custom-scrollbar space-y-1.5 pr-1">
                            @forelse ($availableUsers as $user)
                                <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border border-transparent hover:border-zinc-100 dark:hover:border-zinc-800"
                                    x-show="searchAdd === '' || '{{ strtolower($user->name) }}'.includes(searchAdd.toLowerCase())">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <flux:avatar size="sm" :name="$user->name" :src="$user->avatar" />
                                        <div class="min-w-0">
                                            <div
                                                class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                                {{ $user->name }}
                                            </div>
                                            <div class="text-xs text-zinc-400 truncate">{{ $user->email }}</div>
                                        </div>
                                    </div>

                                    <button type="button" wire:click="addUserToGroup({{ $user->id }})"
                                        class="px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors flex items-center gap-1.5 cursor-pointer shadow-sm">
                                        <flux:icon name="plus" class="w-3.5 h-3.5" />
                                        <span>{{ __('Add to Group') }}</span>
                                    </button>
                                </div>
                            @empty
                                <div class="py-8 text-center text-zinc-400 text-sm">
                                    <flux:icon name="check-circle"
                                        class="w-8 h-8 mx-auto mb-2 text-zinc-300 dark:text-zinc-600" />
                                    {{ __('All registered users are already members of this group.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif

                <!-- Modal Footer -->
                <div class="flex items-center justify-between pt-4 mt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <div>
                        @if ($this->selectedGroup && $this->selectedGroup->owner_id === auth()->id())
                            <button type="button"
                                wire:click="openDeleteGroupModal"
                                class="px-3 py-1.5 text-xs font-semibold text-red-600 hover:text-white hover:bg-red-600 border border-red-200 dark:border-red-800/60 rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer">
                                <flux:icon name="trash" class="w-3.5 h-3.5" />
                                <span>{{ __('Delete Group') }}</span>
                            </button>
                        @endif
                    </div>
                    <button type="button" @click="open = false"
                        class="px-4 py-2 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors cursor-pointer">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Forward Message Modal -->
    <div x-data="{ open: @entangle('showForwardModal'), search: '' }" x-show="open" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-zinc-900/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="relative w-full max-w-md p-4 mx-auto" @click.away="open = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-800 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Forward to...') }}</h3>
                    <button @click="open = false"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-pointer">
                        <flux:icon name="x-mark" class="w-5 h-5" />
                    </button>
                </div>

                <div class="relative mb-4">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <flux:icon name="magnifying-glass" class="w-4 h-4 text-zinc-400" />
                    </div>
                    <input x-model="search" type="text" placeholder="{{ __('Search users or groups...') }}"
                        class="w-full bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl pl-9 pr-4 py-2 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 outline-none">
                </div>

                <div class="max-h-64 overflow-y-auto custom-scrollbar -mx-2 px-2">
                    <!-- Groups -->
                    @if (count($groups) > 0)
                        <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2 mt-2">
                            {{ __('Groups') }}</div>
                        @foreach ($groups as $group)
                            <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 rounded-lg cursor-pointer transition-colors"
                                x-show="search === '' || '{{ strtolower($group->name) }}'.includes(search.toLowerCase())"
                                wire:click="forwardMessageToGroup({{ $group->id }})">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                                        <flux:icon name="user-group"
                                            class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                    </div>
                                    <span
                                        class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $group->name }}</span>
                                </div>
                                <flux:icon name="paper-airplane" class="w-4 h-4 text-indigo-500" />
                            </div>
                        @endforeach
                    @endif

                    <!-- Users -->
                    <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2 mt-4">
                        {{ __('Users') }}</div>
                    @foreach ($users as $u)
                        <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 rounded-lg cursor-pointer transition-colors"
                            x-show="search === '' || '{{ strtolower($u->name) }}'.includes(search.toLowerCase())"
                            wire:click="forwardMessageToUser({{ $u->id }})">
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" :name="$u->name" :src="$u->avatar" />
                                <span
                                    class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $u->name }}</span>
                            </div>
                            <flux:icon name="paper-airplane" class="w-4 h-4 text-indigo-500" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Clear Chat Confirmation Modal -->
    <div x-data="{ open: @entangle('showClearChatModal') }" x-show="open" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-zinc-900/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.away="open = false"
            class="relative w-full max-w-md p-6 bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl m-4 border border-zinc-200 dark:border-zinc-800"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            <div class="flex items-start gap-4 mb-4">
                <div
                    class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-950/60 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                    <flux:icon name="trash" class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                        {{ $this->selectedGroup ? __('Clear Group Chat') : __('Clear Chat') }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1.5 leading-relaxed">
                        @if ($this->selectedGroup)
                            {{ __('Clearing this chat will remove messages only from your chat view. Other members of this group will still be able to see the messages.') }}
                        @else
                            {{ __('Clearing this chat will remove messages only from your chat view. Other participants can still see them.') }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                <button type="button" @click="open = false"
                    class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors cursor-pointer">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="clearChat"
                    class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                    <flux:icon name="trash" class="w-3.5 h-3.5" />
                    <span>{{ __('Clear for me') }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Group Confirmation Modal -->
    <div x-data="{ open: @entangle('showDeleteGroupModal') }" x-show="open" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-zinc-900/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.away="open = false"
            class="relative w-full max-w-md p-6 bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl m-4 border border-zinc-200 dark:border-zinc-800"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            <div class="flex items-start gap-4 mb-4">
                <div
                    class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-950/60 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                    <flux:icon name="exclamation-triangle" class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                        {{ __('Delete Group?') }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1.5 leading-relaxed">
                        {{ __('Are you sure you want to completely delete this group? All messages, media, and member associations will be permanently removed for everyone. This action cannot be undone.') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                <button type="button" @click="open = false"
                    class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors cursor-pointer">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="deleteGroup"
                    class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                    <flux:icon name="trash" class="w-3.5 h-3.5" />
                    <span>{{ __('Delete Group') }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Leave Group Confirmation Modal -->
    <div x-data="{ open: @entangle('showLeaveGroupModal') }" x-show="open" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-zinc-900/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.away="open = false"
            class="relative w-full max-w-md p-6 bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl m-4 border border-zinc-200 dark:border-zinc-800"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            <div class="flex items-start gap-4 mb-4">
                <div
                    class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                    <flux:icon name="arrow-right-start-on-rectangle" class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                        {{ __('Leave Group?') }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1.5 leading-relaxed">
                        {{ __('Are you sure you want to leave “:name”? You will no longer receive messages or be able to participate in this group unless re-added by an admin.', ['name' => $this->selectedGroup?->name]) }}
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                <button type="button" @click="open = false"
                    class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors cursor-pointer">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="leaveGroup"
                    class="px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                    <flux:icon name="arrow-right-start-on-rectangle" class="w-3.5 h-3.5" />
                    <span>{{ __('Leave Group') }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Message Modal -->
    <div x-data="{ open: @entangle('showDeleteMessageModal'), confirmEveryone: @entangle('confirmDeleteForEveryone') }" x-show="open" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto overflow-x-hidden bg-zinc-900/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.away="open = false; confirmEveryone = false"
            class="relative w-full max-w-md p-6 bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl m-4 border border-zinc-200 dark:border-zinc-800"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            <!-- Step 1: Selection View -->
            <div x-show="!confirmEveryone">
                <div class="flex items-start gap-4 mb-5">
                    <div
                        class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-950/60 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                        <flux:icon name="trash" class="w-5 h-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                            {{ __('Delete message?') }}
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            {{ __('Choose an option below to delete this message.') }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2 mb-5">
                    @if ($canDeleteForEveryone)
                        <button type="button" @click="confirmEveryone = true"
                            class="w-full text-left p-3 rounded-xl border border-red-200 dark:border-red-900/50 bg-red-50/50 dark:bg-red-950/20 hover:bg-red-100/60 dark:hover:bg-red-950/40 transition-colors flex items-center justify-between group cursor-pointer">
                            <div>
                                <div class="text-sm font-semibold text-red-700 dark:text-red-400">
                                    {{ __('Delete for everyone') }}
                                </div>
                                <div class="text-xs text-red-600/80 dark:text-red-400/70 mt-0.5">
                                    {{ __('Replaces this message with “This message was deleted” for all participants.') }}
                                </div>
                            </div>
                            <flux:icon name="chevron-right"
                                class="w-4 h-4 text-red-400 group-hover:translate-x-0.5 transition-transform" />
                        </button>
                    @endif

                    <button type="button" wire:click="deleteForMe"
                        class="w-full text-left p-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors flex items-center justify-between group cursor-pointer">
                        <div>
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __('Delete for me') }}
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                {{ __('Removes this message only from your chat view. Other participants can still see it.') }}
                            </div>
                        </div>
                        <flux:icon name="chevron-right"
                            class="w-4 h-4 text-zinc-400 group-hover:translate-x-0.5 transition-transform" />
                    </button>
                </div>

                <div class="flex items-center justify-end pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <button type="button" @click="open = false; confirmEveryone = false"
                        class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>

            <!-- Step 2: Confirmation Dialog Before Deleting For Everyone -->
            <div x-show="confirmEveryone">
                <div class="flex items-start gap-4 mb-4">
                    <div
                        class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-950/60 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                        <flux:icon name="exclamation-triangle" class="w-5 h-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                            {{ __('Delete for everyone?') }}
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1.5 leading-relaxed">
                            {{ __('Are you sure you want to delete this message for everyone? The content will be removed for all participants and replaced with “This message was deleted.” This cannot be undone.') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <button type="button" @click="confirmEveryone = false"
                        class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors cursor-pointer">
                        {{ __('Back') }}
                    </button>
                    <button type="button" wire:click="deleteForEveryone"
                        class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                        <flux:icon name="trash" class="w-3.5 h-3.5" />
                        <span>{{ __('Delete for everyone') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function chatSearchComponent() {
            return {
                open: false,
                query: '',
                matches: [],
                currentIndex: -1,
                showDropdown: true,

                openSearch() {
                    this.open = true;
                    this.showDropdown = true;
                    this.$nextTick(() => {
                        if (this.$refs.searchInput) {
                            this.$refs.searchInput.focus();
                            this.$refs.searchInput.select();
                        }
                    });
                },

                closeSearch() {
                    this.open = false;
                    this.query = '';
                    this.matches = [];
                    this.currentIndex = -1;
                    this.showDropdown = false;
                },

                performSearch() {
                    const q = this.query.trim().toLowerCase();
                    if (!q) {
                        this.matches = [];
                        this.currentIndex = -1;
                        return;
                    }

                    const nodes = document.querySelectorAll('#chat-messages [data-searchable-message="true"]');
                    const results = [];

                    nodes.forEach(node => {
                        const text = (node.getAttribute('data-message-text') || '');
                        if (text.toLowerCase().includes(q)) {
                            results.push({
                                id: node.getAttribute('data-message-id'),
                                rawText: text,
                                sender: node.getAttribute('data-sender-name') || '',
                                time: node.getAttribute('data-time') || '',
                            });
                        }
                    });

                    this.matches = results;
                    this.currentIndex = results.length > 0 ? 0 : -1;
                    this.showDropdown = true;

                    if (results.length > 0) {
                        this.jumpTo(results[0].id, false);
                    }
                },

                nextMatch() {
                    if (this.matches.length === 0) return;
                    this.currentIndex = (this.currentIndex + 1) % this.matches.length;
                    this.jumpTo(this.matches[this.currentIndex].id, true);
                },

                prevMatch() {
                    if (this.matches.length === 0) return;
                    this.currentIndex = (this.currentIndex - 1 + this.matches.length) % this.matches.length;
                    this.jumpTo(this.matches[this.currentIndex].id, true);
                },

                jumpTo(id, highlight = true) {
                    highlightMessage(id);
                },

                formatSnippet(text, query) {
                    if (!query || !text) return text || '';
                    const lowerText = text.toLowerCase();
                    const lowerQuery = query.toLowerCase();
                    const index = lowerText.indexOf(lowerQuery);
                    if (index === -1) return text.substring(0, 80);

                    const start = Math.max(0, index - 25);
                    const end = Math.min(text.length, index + query.length + 45);
                    let snippet = text.substring(start, end);
                    if (start > 0) snippet = '...' + snippet;
                    if (end < text.length) snippet = snippet + '...';

                    const safeQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const regex = new RegExp(`(${safeQuery})`, 'gi');
                    return snippet.replace(regex,
                        '<mark class="bg-amber-300 dark:bg-amber-400 text-zinc-900 font-semibold px-0.5 rounded">$1</mark>'
                    );
                }
            };
        }

        function highlightMessage(id) {
            const el = document.getElementById('message-' + id);
            if (el) {
                el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                const bubble = el.querySelector('.bubble-container');
                if (bubble) {
                    bubble.classList.add('ring-2', 'ring-indigo-500', 'ring-offset-2', 'dark:ring-offset-zinc-900',
                        'scale-[1.02]');
                    setTimeout(() => {
                        bubble.classList.remove('ring-2', 'ring-indigo-500', 'ring-offset-2',
                            'dark:ring-offset-zinc-900', 'scale-[1.02]');
                    }, 800);
                }
            }
        }

        function tagComposer(initialUsers, hasInitialAttachments = false) {
            return {
                users: initialUsers || [],
                showTagList: false,
                tagQuery: '',
                selectedIndex: 0,
                mentionStartIndex: -1,
                justSelected: false,
                messageText: '',
                hasAttachments: Boolean(hasInitialAttachments),

                init() {
                    const input = document.getElementById('message-input');
                    if (input) {
                        this.messageText = input.value || '';
                    }
                    this.$watch('$wire.newMessage', val => {
                        this.messageText = val || '';
                    });
                },

                canSend() {
                    return (this.messageText && this.messageText.trim().length > 0) || this.hasAttachments;
                },

                filteredUsers() {
                    if (!this.tagQuery) return this.users;
                    const q = this.tagQuery.toLowerCase();
                    return this.users.filter(u =>
                        u.name.toLowerCase().includes(q) ||
                        (u.email && u.email.toLowerCase().includes(q))
                    );
                },

                handleInput(e) {
                    const el = e.target;
                    const val = el.value;
                    this.messageText = val || '';

                    if (this.justSelected) {
                        this.justSelected = false;
                        this.showTagList = false;
                        return;
                    }

                    const cursor = el.selectionStart;

                    const textBeforeCursor = val.substring(0, cursor);
                    const atIndex = textBeforeCursor.lastIndexOf('@');

                    if (atIndex !== -1) {
                        const query = textBeforeCursor.substring(atIndex + 1);
                        const charBeforeAt = atIndex > 0 ? val[atIndex - 1] : ' ';
                        if ((charBeforeAt === ' ' || charBeforeAt === '\n' || atIndex === 0) && !query.includes('\n') &&
                            query.length < 30) {
                            const spaces = (query.match(/ /g) || []).length;
                            if (spaces <= 1 && !query.endsWith('  ')) {
                                this.mentionStartIndex = atIndex;
                                this.tagQuery = query;
                                this.showTagList = true;
                                this.selectedIndex = 0;
                                return;
                            }
                        }
                    }

                    this.showTagList = false;
                },

                handleKeyDown(e) {
                    const isListOpen = this.showTagList && this.filteredUsers().length > 0;

                    if (isListOpen) {
                        const matches = this.filteredUsers();
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            this.selectedIndex = (this.selectedIndex + 1) % matches.length;
                            return;
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            this.selectedIndex = (this.selectedIndex - 1 + matches.length) % matches.length;
                            return;
                        } else if (e.key === 'Enter' || e.key === 'Tab') {
                            e.preventDefault();
                            this.selectUser(matches[this.selectedIndex]);
                            return;
                        } else if (e.key === 'Escape') {
                            e.preventDefault();
                            this.showTagList = false;
                            return;
                        }
                    }

                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.showTagList = false;
                        if (!this.canSend()) {
                            return;
                        }
                        const form = document.getElementById('message-input')?.closest('form');
                        if (form) {
                            form.requestSubmit();
                        } else if (this.$wire && typeof this.$wire.submit === 'function') {
                            this.$wire.submit();
                        }
                    }
                },

                selectUser(user) {
                    if (!user) return;
                    const input = document.getElementById('message-input');
                    if (!input) return;

                    const val = input.value;
                    const startIndex = this.mentionStartIndex >= 0 ? this.mentionStartIndex : 0;
                    const queryLength = (this.tagQuery || '').length;
                    const before = val.substring(0, startIndex);
                    const after = val.substring(startIndex + 1 + queryLength);
                    const mention = `@${user.name} `;

                    input.value = before + mention + after;
                    this.messageText = input.value;
                    const newCursor = before.length + mention.length;
                    input.selectionStart = input.selectionEnd = newCursor;

                    this.showTagList = false;
                    this.tagQuery = '';
                    this.mentionStartIndex = -1;
                    this.justSelected = true;

                    input.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    input.focus();
                },

                triggerTag() {
                    const input = document.getElementById('message-input');
                    if (!input) return;
                    const start = (input.selectionStart !== null && input.selectionStart !== undefined) ? input
                        .selectionStart : input.value.length;
                    const val = input.value;
                    input.value = val.substring(0, start) + '@' + val.substring(start);
                    input.selectionStart = input.selectionEnd = start + 1;
                    input.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    input.focus();
                    this.handleInput({
                        target: input
                    });
                }
            };
        }

        function chatMessagesScrollArea() {
            return {
                showScrollDown: false,
                unreadBelow: 0,
                activeConversation: null,
                lastMessageId: null,
                lastCount: 0,

                checkScroll() {
                    const el = this.$refs.messagesContainer || document.getElementById('chat-messages');
                    if (!el) return;
                    const distanceToBottom = el.scrollHeight - el.scrollTop - el.clientHeight;
                    if (distanceToBottom > 100) {
                        this.showScrollDown = true;
                    } else {
                        this.showScrollDown = false;
                        this.unreadBelow = 0;
                    }
                },

                scrollToBottom(smooth = false) {
                    const el = this.$refs.messagesContainer || document.getElementById('chat-messages');
                    if (!el) return;
                    if (smooth) {
                        el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
                    } else {
                        el.scrollTop = el.scrollHeight;
                    }
                    this.showScrollDown = false;
                    this.unreadBelow = 0;
                },

                init() {
                    const el = this.$refs.messagesContainer || document.getElementById('chat-messages');
                    if (!el) return;

                    this.activeConversation = el.dataset.conversation;
                    const msgs = el.querySelectorAll('[id^=message-]');
                    this.lastCount = msgs.length;
                    this.lastMessageId = msgs.length > 0 ? msgs[msgs.length - 1].id : null;

                    setTimeout(() => this.scrollToBottom(false), 50);
                    setTimeout(() => this.scrollToBottom(false), 200);

                    const observer = new MutationObserver(() => {
                        const container = this.$refs.messagesContainer || document.getElementById('chat-messages');
                        if (!container) return;

                        const currentConv = container.dataset.conversation;
                        const currentMsgs = container.querySelectorAll('[id^=message-]');
                        const currentCount = currentMsgs.length;
                        const currentLast = currentCount > 0 ? currentMsgs[currentCount - 1].id : null;

                        // 1. If conversation changed (switched user or group)
                        if (currentConv !== this.activeConversation) {
                            this.activeConversation = currentConv;
                            this.lastMessageId = currentLast;
                            this.lastCount = currentCount;
                            this.showScrollDown = false;
                            this.unreadBelow = 0;
                            setTimeout(() => this.scrollToBottom(false), 30);
                            return;
                        }

                        // 2. If a new message was added at the bottom
                        if (currentCount > this.lastCount && currentLast && currentLast !== this.lastMessageId) {
                            const newCount = currentCount - this.lastCount;
                            this.lastMessageId = currentLast;
                            this.lastCount = currentCount;

                            const distanceToBottom = container.scrollHeight - container.scrollTop - container.clientHeight;
                            // If user is scrolled up reading previous messages, keep position and show WhatsApp down button
                            if (distanceToBottom > 120) {
                                this.showScrollDown = true;
                                this.unreadBelow += newCount;
                                return;
                            }

                            // User is already near the bottom, scroll down smoothly
                            setTimeout(() => this.scrollToBottom(true), 30);
                            return;
                        }

                        // 3. Any other update (reactions, edits, pins) -> preserve scroll
                        this.lastMessageId = currentLast;
                        this.lastCount = currentCount;
                    });

                    observer.observe(el, { childList: true, subtree: false, attributes: true, attributeFilter: ['data-conversation'] });

                    if (this.$wire) {
                        this.$watch('$wire.selectedUserId', () => {
                            this.showScrollDown = false;
                            this.unreadBelow = 0;
                            setTimeout(() => this.scrollToBottom(false), 50);
                        });
                        this.$watch('$wire.selectedGroupId', () => {
                            this.showScrollDown = false;
                            this.unreadBelow = 0;
                            setTimeout(() => this.scrollToBottom(false), 50);
                        });
                    }
                }
            };
        }
    </script>
</div>
