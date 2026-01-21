<x-filament-panels::page>
    <div class="max-w-2xl mx-auto">
        <div class="upload-zone"
             x-data="{ isDragging: false, progress: 0, uploading: false }"
             x-on:dragover.prevent="isDragging = true"
             x-on:dragleave.prevent="isDragging = false"
             x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
             x-on:livewire-upload-start="uploading = true"
             x-on:livewire-upload-finish="uploading = false; progress = 0"
             x-on:livewire-upload-error="uploading = false"
             x-on:livewire-upload-progress="progress = $event.detail.progress">

            {{-- Visual drop zone --}}
            <div :class="{ 'border-primary-500 bg-primary-50 dark:bg-primary-950': isDragging }"
                 class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-12 text-center transition-colors duration-200">

                <x-heroicon-o-cloud-arrow-up class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500" />

                <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                    Drag and drop your zip file here
                </p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    or
                </p>

                {{-- Click to upload button (fallback) --}}
                <label class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg cursor-pointer hover:bg-primary-700 transition-colors duration-200">
                    <span>Browse Files</span>
                    <input type="file"
                           wire:model="archive"
                           x-ref="fileInput"
                           accept=".zip,application/zip,application/x-zip-compressed"
                           class="hidden" />
                </label>

                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                    Accepted: .zip files up to 100MB
                </p>
            </div>

            {{-- Progress bar during upload --}}
            <div x-show="uploading" x-cloak class="mt-4">
                <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Uploading...</span>
                        <span class="text-sm text-gray-600 dark:text-gray-300" x-text="progress + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                             :style="'width: ' + progress + '%'"></div>
                    </div>
                    {{-- Cancel upload button --}}
                    <button type="button"
                            x-on:click="$wire.cancelUpload('archive')"
                            class="mt-3 text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                        Cancel upload
                    </button>
                </div>
            </div>
        </div>

        {{-- Validation error display --}}
        @error('archive')
            <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-center">
                    <x-heroicon-o-exclamation-circle class="w-5 h-5 text-red-500 dark:text-red-400 mr-2 flex-shrink-0" />
                    <span class="text-sm text-red-700 dark:text-red-300">{{ $message }}</span>
                </div>
            </div>
        @enderror

        {{-- Selected file display (only show if no validation errors) --}}
        @if($archive && !$errors->has('archive'))
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center min-w-0">
                        <x-heroicon-o-document class="w-8 h-8 text-gray-400 dark:text-gray-500 flex-shrink-0" />
                        <div class="ml-3 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $archive->getClientOriginalName() }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ number_format($archive->getSize() / 1024 / 1024, 2) }} MB
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 ml-4">
                        {{-- Replace button --}}
                        <label class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 cursor-pointer">
                            Replace
                            <input type="file"
                                   wire:model="archive"
                                   accept=".zip,application/zip,application/x-zip-compressed"
                                   class="hidden" />
                        </label>
                        {{-- Remove button --}}
                        <button type="button"
                                wire:click="removeFile"
                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            {{-- Zip contents preview --}}
            @if(!empty($preview))
                <div class="mt-4 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        <x-heroicon-o-folder-open class="w-4 h-4 inline-block mr-1" />
                        Zip Contents Preview
                    </h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        @foreach($preview as $folder => $files)
                            <div class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-folder class="w-4 h-4 inline-block mr-1 text-yellow-500" />
                                    {{ $folder }}
                                    <span class="text-xs text-gray-500">({{ count($files) }} files)</span>
                                </p>
                                <ul class="mt-1 ml-5 text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                                    @foreach($files as $file)
                                        <li class="flex items-center">
                                            <x-heroicon-o-document class="w-3 h-3 mr-1 flex-shrink-0" />
                                            {{ $file }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ count($preview) }} folder(s) found
                    </p>
                </div>
            @endif

            {{-- Validation results --}}
            @if($validationResult)
                @if($validationResult['valid'])
                    <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-center">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" />
                            <span class="text-sm font-medium text-green-700 dark:text-green-300">
                                Validation Passed - All folders contain required files
                            </span>
                        </div>
                        <div class="mt-3 ml-7">
                            <p class="text-xs text-green-600 dark:text-green-400">
                                {{ count($validationResult['structure']) }} folder(s) validated successfully.
                                Ready for extraction in Phase 4.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="flex items-center">
                            <x-heroicon-o-x-circle class="w-5 h-5 text-red-500 mr-2 flex-shrink-0" />
                            <span class="text-sm font-medium text-red-700 dark:text-red-300">
                                Validation Failed - Missing required files
                            </span>
                        </div>
                        <div class="mt-3 space-y-3">
                            @foreach($validationResult['errors'] as $folder => $errors)
                                <div class="ml-7 p-3 bg-red-100 dark:bg-red-900/30 rounded">
                                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                        <x-heroicon-o-folder class="w-4 h-4 inline-block mr-1" />
                                        {{ $folder === '_root' ? 'Archive Structure' : $folder }}
                                    </p>
                                    <ul class="mt-2 space-y-1">
                                        @foreach($errors as $error)
                                            <li class="text-xs text-red-700 dark:text-red-300 flex items-start">
                                                <x-heroicon-o-exclamation-triangle class="w-3 h-3 mr-1 mt-0.5 flex-shrink-0" />
                                                {{ $error }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-3 ml-7 text-xs text-red-600 dark:text-red-400">
                            Please fix the issues above and upload a corrected archive.
                        </p>
                    </div>
                @endif
            @endif

            {{-- Submit button --}}
            <div class="mt-6">
                <button type="button"
                        wire:click="submit"
                        wire:loading.attr="disabled"
                        @if($validating) disabled @endif
                        class="w-full px-4 py-3 bg-primary-600 text-white rounded-lg font-medium
                               hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
                               disabled:opacity-50 disabled:cursor-not-allowed
                               transition-colors duration-200">
                    @if($validating)
                        <span class="flex items-center justify-center">
                            <x-filament::loading-indicator class="h-5 w-5 mr-2" />
                            Validating...
                        </span>
                    @else
                        <span wire:loading.remove wire:target="submit">Validate and Process</span>
                        <span wire:loading wire:target="submit" class="flex items-center justify-center">
                            <x-filament::loading-indicator class="h-5 w-5 mr-2" />
                            Processing...
                        </span>
                    @endif
                </button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
