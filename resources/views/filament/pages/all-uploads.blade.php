<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Statistics Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center">
                        <x-heroicon-o-users class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_users'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Users</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                        <x-heroicon-o-archive-box class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_uploads'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Uploads</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                        <x-heroicon-o-folder class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_folders'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Folders</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                        <x-heroicon-o-clock class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        @if($stats['latest_upload'])
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($stats['latest_upload'])->format('M j') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($stats['latest_upload'])->format('g:i A') }}</p>
                        @else
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">No uploads</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">-</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter and Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            {{-- Sidebar Filter --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden sticky top-4">
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Filter by User</h3>
                    </div>
                    <div class="p-2">
                        <button type="button"
                                wire:click="filterByUser('')"
                                class="w-full px-3 py-2 text-left text-sm rounded-lg transition-colors {{ !$selectedUserId ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                            All Users
                        </button>
                        @foreach($users as $user)
                            <button type="button"
                                    wire:click="filterByUser('{{ $user['id'] }}')"
                                    class="w-full px-3 py-2 text-left text-sm rounded-lg transition-colors flex items-center gap-2 {{ $selectedUserId == $user['id'] ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                                <span class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-400">
                                    {{ strtoupper(substr($user['full_name'], 0, 1)) }}
                                </span>
                                <span class="truncate">{{ $user['full_name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="lg:col-span-3">
                @if(count($history) > 0)
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h2 class="text-sm font-medium text-gray-900 dark:text-white">
                                @if($selectedUserId)
                                    Uploads by {{ collect($users)->firstWhere('id', $selectedUserId)['full_name'] ?? 'User' }}
                                @else
                                    All Uploads
                                @endif
                            </h2>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($history) }} {{ count($history) === 1 ? 'upload' : 'uploads' }}</span>
                        </div>

                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($history as $entry)
                                <div>
                                    {{-- Entry Header --}}
                                    <button type="button"
                                            wire:click="{{ $selectedUpload === $entry['user']->id . '-' . $entry['datetime'] ? 'closeDetails' : "viewUpload({$entry['user']->id}, '{$entry['datetime']}')" }}"
                                            class="w-full p-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors text-left">
                                        {{-- User Avatar --}}
                                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center">
                                            <span class="text-sm font-medium text-white">
                                                {{ strtoupper(substr($entry['user']->full_name, 0, 2)) }}
                                            </span>
                                        </div>

                                        {{-- Info Grid --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-4">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $entry['user']->full_name }}
                                                </p>
                                                <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="flex items-center gap-1">
                                                        <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                                                        {{ \Carbon\Carbon::parse($entry['datetime'])->format('M j, Y') }}
                                                    </span>
                                                    <span class="flex items-center gap-1">
                                                        <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                                        {{ \Carbon\Carbon::parse($entry['datetime'])->format('g:i A') }}
                                                    </span>
                                                    <span class="flex items-center gap-1">
                                                        <x-heroicon-o-folder class="w-3.5 h-3.5" />
                                                        {{ $entry['folders'] }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Toggle --}}
                                        <div class="flex-shrink-0">
                                            @if($selectedUpload === $entry['user']->id . '-' . $entry['datetime'])
                                                <x-heroicon-o-chevron-up class="w-5 h-5 text-primary-500" />
                                            @else
                                                <x-heroicon-o-chevron-down class="w-5 h-5 text-gray-400" />
                                            @endif
                                        </div>
                                    </button>

                                    {{-- Expanded Content --}}
                                    @if($selectedUpload === $entry['user']->id . '-' . $entry['datetime'])
                                        <div class="px-4 pb-4">
                                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                                                @if(count($uploadContents) > 0)
                                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                                                        Folders in this upload
                                                    </p>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        @foreach($uploadContents as $folder)
                                                            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                                                <div class="flex items-center gap-2 mb-2">
                                                                    <x-heroicon-s-folder class="w-5 h-5 text-yellow-500 flex-shrink-0" />
                                                                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                                        {{ $folder['folder_name'] }}
                                                                    </span>
                                                                    <span class="ml-auto text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">
                                                                        {{ $folder['file_count'] }}
                                                                    </span>
                                                                </div>

                                                                @if(count($folder['files']) > 0)
                                                                    <div class="space-y-1 max-h-28 overflow-y-auto">
                                                                        @foreach($folder['files'] as $file)
                                                                            <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400 pl-7">
                                                                                <x-heroicon-o-document class="w-3 h-3 flex-shrink-0 text-gray-400" />
                                                                                <span class="truncate">{{ $file }}</span>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="text-center py-4">
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">No folders found in this upload.</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl">
                        <div class="flex flex-col items-center justify-center py-16">
                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                                <x-heroicon-o-archive-box class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                No uploads found
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                                @if($selectedUserId)
                                    This user has no uploads in the Done folder yet.
                                @else
                                    No users have uploads in the Done folder yet.
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
