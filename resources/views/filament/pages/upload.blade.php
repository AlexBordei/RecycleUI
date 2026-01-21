<x-filament-panels::page>
    <div class="max-w-2xl mx-auto">
        <div class="upload-zone"
             x-data="{ isDragging: false }"
             x-on:dragover.prevent="isDragging = true"
             x-on:dragleave.prevent="isDragging = false"
             x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))">

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
                           accept=".zip,application/zip"
                           class="hidden" />
                </label>

                <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                    Only .zip files are accepted
                </p>
            </div>
        </div>

        {{-- Loading indicator during upload --}}
        <div wire:loading wire:target="archive" class="mt-4">
            <div class="flex items-center justify-center p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Uploading...</span>
            </div>
        </div>

        {{-- Selected file display --}}
        @if($archive)
            <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center min-w-0">
                        <x-heroicon-o-document class="w-8 h-8 text-gray-400 dark:text-gray-500 flex-shrink-0" />
                        <div class="ml-3 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $archive->getClientOriginalName() }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ number_format($archive->getSize() / 1024, 2) }} KB
                            </p>
                        </div>
                    </div>
                    <button type="button"
                            wire:click="removeFile"
                            class="ml-4 p-1 text-gray-400 hover:text-red-500 transition-colors duration-200"
                            title="Remove file">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
