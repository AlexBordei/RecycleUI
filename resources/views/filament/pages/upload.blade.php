<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Upload Zone Section --}}
        <div x-data="{ isDragging: false, progress: 0, uploading: false }"
             x-on:dragover.prevent="isDragging = true"
             x-on:dragleave.prevent="isDragging = false"
             x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
             x-on:livewire-upload-start="uploading = true"
             x-on:livewire-upload-finish="uploading = false; progress = 0"
             x-on:livewire-upload-error="uploading = false"
             x-on:livewire-upload-progress="progress = $event.detail.progress">

            <div :class="{
                    'border-primary-500 bg-primary-50 dark:bg-primary-950/50 scale-[1.02]': isDragging,
                    'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900': !isDragging
                 }"
                 class="relative border-2 border-dashed rounded-2xl p-12 text-center transition-all duration-300 ease-out">

                <div class="flex flex-col items-center justify-center space-y-4">
                    <div :class="{ 'scale-110 text-primary-500': isDragging }"
                         class="transition-transform duration-300">
                        <x-heroicon-o-cloud-arrow-up class="w-16 h-16 text-gray-400 dark:text-gray-500" />
                    </div>

                    <div class="space-y-2">
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            Drag and drop your zip file here
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            or click the button below to browse
                        </p>
                    </div>

                    <label class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg cursor-pointer hover:bg-primary-700 active:bg-primary-800 transition-colors">
                        <x-heroicon-o-folder-open class="w-4 h-4" />
                        <span>Browse Files</span>
                        <input type="file"
                               wire:model="archive"
                               x-ref="fileInput"
                               accept=".zip,application/zip,application/x-zip-compressed"
                               class="hidden" />
                    </label>

                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Accepted: .zip files up to 100MB
                    </p>
                </div>

                {{-- Progress overlay --}}
                <div x-show="uploading"
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="absolute inset-0 bg-white/90 dark:bg-gray-900/90 rounded-2xl flex flex-col items-center justify-center">
                    <div class="w-48 space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-300 font-medium">Uploading...</span>
                            <span class="text-primary-600 dark:text-primary-400 font-semibold" x-text="progress + '%'"></span>
                        </div>
                        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-600 rounded-full transition-all duration-300 ease-out"
                                 :style="'width: ' + progress + '%'"></div>
                        </div>
                        <button type="button"
                                x-on:click="$wire.cancelUpload('archive')"
                                class="w-full text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Validation error --}}
        @error('archive')
            <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                <x-heroicon-s-exclamation-circle class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                <p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
            </div>
        @enderror

        {{-- Selected file and preview --}}
        @if($archive && !$errors->has('archive'))
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Left column: File info + Actions --}}
                <div class="space-y-4">
                    {{-- File info card --}}
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                                <x-heroicon-o-document-arrow-up class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $archive->getClientOriginalName() }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ number_format($archive->getSize() / 1024 / 1024, 2) }} MB
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <label class="flex-1 text-center text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium cursor-pointer py-2 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                                Replace
                                <input type="file"
                                       wire:model="archive"
                                       accept=".zip,application/zip,application/x-zip-compressed"
                                       class="hidden" />
                            </label>
                            <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>
                            <button type="button"
                                    wire:click="removeFile"
                                    class="flex-1 text-center text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                Remove
                            </button>
                        </div>
                    </div>

                    {{-- Validation status --}}
                    @if($validationResult)
                        @if($validationResult['valid'])
                            @if(!$extractionResult || !$extractionResult['success'])
                                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                            <x-heroicon-s-check class="w-5 h-5 text-green-600 dark:text-green-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-green-800 dark:text-green-200">Validation Passed</p>
                                            <p class="text-xs text-green-600 dark:text-green-400 mt-0.5">
                                                {{ count($validationResult['structure']) }} folder(s) validated
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="flex-shrink-0 w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                        <x-heroicon-s-x-mark class="w-5 h-5 text-red-600 dark:text-red-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-red-800 dark:text-red-200">Validation Failed</p>
                                        <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">Missing required files</p>
                                    </div>
                                </div>

                                <div class="space-y-3 max-h-64 overflow-y-auto">
                                    @foreach($validationResult['errors'] as $folder => $errors)
                                        <div class="bg-red-100/50 dark:bg-red-900/30 rounded-lg p-3">
                                            <p class="text-xs font-medium text-red-800 dark:text-red-200 flex items-center gap-1.5">
                                                <x-heroicon-o-folder class="w-4 h-4" />
                                                {{ $folder === '_root' ? 'Archive Structure' : $folder }}
                                            </p>
                                            <ul class="mt-2 space-y-1">
                                                @foreach($errors as $error)
                                                    <li class="text-xs text-red-700 dark:text-red-300 flex items-start gap-1.5 pl-5">
                                                        <x-heroicon-o-exclamation-triangle class="w-3 h-3 mt-0.5 flex-shrink-0" />
                                                        {{ $error }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Extraction success --}}
                    @if($extractionResult && $extractionResult['success'])
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                    <x-heroicon-s-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-green-800 dark:text-green-200">Upload Complete</p>
                                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $extractionResult['message'] }}</p>
                                    <p class="text-xs text-green-600/80 dark:text-green-400/80 font-mono mt-2 truncate">
                                        {{ $extractionResult['path'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Submit button --}}
                    <button type="button"
                            wire:click="submit"
                            wire:loading.attr="disabled"
                            @if($validating) disabled @endif
                            class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg font-medium
                                   hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
                                   disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        @if($validating)
                            <x-filament::loading-indicator class="h-5 w-5" />
                            <span>Validating...</span>
                        @else
                            <span wire:loading.remove wire:target="submit">
                                <x-heroicon-o-arrow-up-tray class="w-5 h-5 inline-block mr-1" />
                                Validate and Process
                            </span>
                            <span wire:loading wire:target="submit" class="flex items-center gap-2">
                                <x-filament::loading-indicator class="h-5 w-5" />
                                Processing...
                            </span>
                        @endif
                    </button>
                </div>

                {{-- Right column: Zip contents preview --}}
                @if(!empty($preview))
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                <x-heroicon-o-folder-open class="w-4 h-4 text-gray-500" />
                                Archive Contents
                                <span class="ml-auto text-xs text-gray-500 dark:text-gray-400 font-normal">
                                    {{ count($preview) }} folder(s)
                                </span>
                            </h3>
                        </div>
                        <div class="p-4 max-h-96 overflow-y-auto">
                            <div class="space-y-3">
                                @foreach($preview as $folder => $files)
                                    <div class="group">
                                        <div class="flex items-center gap-2 text-sm">
                                            <x-heroicon-s-folder class="w-5 h-5 text-yellow-500 flex-shrink-0" />
                                            <span class="font-medium text-gray-900 dark:text-white truncate">{{ $folder }}</span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500 ml-auto">({{ count($files) }})</span>
                                        </div>
                                        <ul class="mt-1.5 ml-7 space-y-1 border-l-2 border-gray-100 dark:border-gray-800 pl-3">
                                            @foreach($files as $file)
                                                <li class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                                                    <x-heroicon-o-document class="w-3 h-3 flex-shrink-0 text-gray-400" />
                                                    <span class="truncate">{{ $file }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
