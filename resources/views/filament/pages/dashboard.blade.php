<x-filament-panels::page>
    @if(count($recentUploads) > 0)
        <div class="space-y-6">
            {{-- Stats Summary --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-archive-box class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($recentUploads) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Recent Uploads</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-folder class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ collect($recentUploads)->sum('folders') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Folders</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-calendar class="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $recentUploads[0]['carbon']->format('M j') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Latest Upload</p>
                        </div>
                    </div>
                </div>

                <a href="{{ route('filament.admin.pages.upload') }}"
                   class="bg-primary-600 hover:bg-primary-700 rounded-xl p-4 transition-colors group">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-plus class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">New Upload</p>
                            <p class="text-xs text-primary-200">Upload archive</p>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Recent Uploads Grid --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activity</h2>
                    <a href="{{ route('filament.admin.pages.history') }}"
                       class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium flex items-center gap-1">
                        View All
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($recentUploads as $upload)
                        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden hover:border-primary-300 dark:hover:border-primary-700 transition-colors group">
                            <div class="p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30 transition-colors">
                                        <x-heroicon-o-archive-box class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $upload['carbon']->format('l, F j') }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ $upload['carbon']->format('Y') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                                    <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                            {{ $upload['carbon']->format('g:i A') }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-o-folder class="w-3.5 h-3.5" />
                                            {{ $upload['folders'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Footer info --}}
            <div class="text-center py-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Showing {{ count($recentUploads) }} most recent {{ count($recentUploads) === 1 ? 'upload' : 'uploads' }}
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
                No uploads yet
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
