<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithFileUploads;

class Upload extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Upload Archive';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Upload Zip Archive';

    protected static string $view = 'filament.pages.upload';

    /**
     * The uploaded archive file.
     */
    public $archive;

    /**
     * Validation rules for the archive file.
     */
    protected $rules = [
        'archive' => [
            'required',
            'file',
            'mimes:zip',
            'max:102400', // 100MB limit
        ],
    ];

    /**
     * Custom validation messages.
     */
    protected $messages = [
        'archive.mimes' => 'Only ZIP files are accepted. Please upload a .zip archive.',
        'archive.max' => 'File size exceeds the 100MB limit. Please upload a smaller archive.',
        'archive.required' => 'Please select a file to upload.',
    ];

    /**
     * Validate the archive when it's updated.
     */
    public function updatedArchive(): void
    {
        $this->validateOnly('archive');
    }

    /**
     * Remove the currently selected file.
     */
    public function removeFile(): void
    {
        $this->archive = null;
        $this->resetValidation('archive');
    }

    /**
     * Cancel an in-progress upload.
     */
    public function cancelUpload(string $property): void
    {
        $this->$property = null;
        $this->resetValidation($property);
    }

    /**
     * Submit the archive for processing.
     */
    public function submit(): void
    {
        $this->validate();

        // Phase 3 will implement actual zip validation
        // For now, just show success notification
        Notification::make()
            ->title('File received')
            ->body('Archive uploaded successfully. Validation coming in Phase 3.')
            ->success()
            ->send();
    }
}
