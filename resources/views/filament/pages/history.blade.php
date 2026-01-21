<x-filament-panels::page>
    @if(count($history) > 0)
        <div class="space-y-6">
            {{-- Stats Bar --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($history) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Uploads</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ collect($history)->sum('folders') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Folders</p>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($history[0]['datetime'])->format('M j, Y') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Most Recent</p>
                </div>
                <a href="{{ route('filament.admin.pages.upload') }}"
                   class="bg-primary-600 hover:bg-primary-700 rounded-xl p-4 transition-colors flex items-center justify-center gap-2">
                    <x-heroicon-o-plus class="w-5 h-5 text-white" />
                    <span class="text-sm font-semibold text-white">New Upload</span>
                </a>
            </div>

            {{-- History List --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-sm font-medium text-gray-900 dark:text-white">Upload History</h2>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($history as $entry)
                        <div x-data="{ expanded: {{ $selectedUpload === $entry['datetime'] ? 'true' : 'false' }} }">
                            {{-- Entry Header --}}
                            <button type="button"
                                    wire:click="{{ $selectedUpload === $entry['datetime'] ? 'closeDetails' : "viewUpload('{$entry['datetime']}')" }}"
                                    class="w-full p-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors text-left">
                                <div class="flex-shrink-0 w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                    <x-heroicon-o-archive-box class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                </div>

                                <div class="flex-1 min-w-0 grid grid-cols-1 md:grid-cols-3 gap-1 md:gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($entry['datetime'])->format('l, F j, Y') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                            {{ \Carbon\Carbon::parse($entry['datetime'])->format('g:i A') }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-o-folder class="w-3.5 h-3.5" />
                                            {{ $entry['folders'] }} {{ $entry['folders'] === 1 ? 'folder' : 'folders' }}
                                        </span>
                                    </div>
                                    <div class="hidden md:flex justify-end">
                                        <span class="inline-flex items-center gap-1 text-xs font-medium {{ $selectedUpload === $entry['datetime'] ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
                                            @if($selectedUpload === $entry['datetime'])
                                                <x-heroicon-o-chevron-up class="w-4 h-4" />
                                                Hide
                                            @else
                                                <x-heroicon-o-chevron-down class="w-4 h-4" />
                                                Details
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </button>

                            {{-- Expanded Content --}}
                            @if($selectedUpload === $entry['datetime'])
                                <div class="px-4 pb-4">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                                        @if(count($uploadContents) > 0)
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                                                Folders in this upload
                                            </p>
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                @foreach($uploadContents as $folder)
                                                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                                        <div class="flex items-center gap-2 mb-2">
                                                            <x-heroicon-s-folder class="w-5 h-5 text-yellow-500 flex-shrink-0" />
                                                            <span class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                                {{ $folder['folder_name'] }}
                                                            </span>
                                                            <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">
                                                                {{ $folder['file_count'] }}
                                                            </span>
                                                        </div>

                                                        @if(count($folder['files']) > 0)
                                                            <div class="space-y-1 max-h-32 overflow-y-auto">
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

            {{-- Footer --}}
            <div class="text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ count($history) }} {{ count($history) === 1 ? 'upload' : 'uploads' }} in your history
                </p>
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center py-16">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-6">
                <x-heroicon-o-archive-box class="w-10 h-10 text-gray-400 dark:text-gray-500" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                No upload history
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 text-center max-w-sm">
                Your processed uploads will appear here once they are moved to the Done folder.
            </p>
            <a href="{{ route('filament.admin.pages.upload') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg font-medium hover:bg-primary-700 transition-colors">
                <x-heroicon-o-arrow-up-tray class="w-4 h-4" />
                Upload Your First Archive
            </a>
        </div>
    @endif
</x-filament-panels::page>
