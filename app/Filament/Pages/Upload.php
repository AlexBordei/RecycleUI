<?php

namespace App\Filament\Pages;

use App\Services\ZipValidatorService;
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
     * Preview of zip structure (folder -> files).
     */
    public array $preview = [];

    /**
     * Whether preview is currently loading.
     */
    public bool $previewLoading = false;

    /**
     * Validation result from ZipValidatorService.
     */
    public ?array $validationResult = null;

    /**
     * Whether validation is in progress.
     */
    public bool $validating = false;

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

        // Generate preview if file is valid
        if (! $this->getErrorBag()->has('archive') && $this->archive) {
            $this->generatePreview();
        } else {
            $this->preview = [];
        }
    }

    /**
     * Generate preview of zip contents.
     */
    public function generatePreview(): void
    {
        if (! $this->archive) {
            return;
        }

        $this->previewLoading = true;

        try {
            $validator = new ZipValidatorService();
            $validator->extract($this->archive);
            $this->preview = $validator->getStructure();
        } catch (\Exception $e) {
            $this->addError('archive', 'Failed to read zip contents: '.$e->getMessage());
            $this->preview = [];
        }

        $this->previewLoading = false;
    }

    /**
     * Remove the currently selected file.
     */
    public function removeFile(): void
    {
        $this->archive = null;
        $this->preview = [];
        $this->validationResult = null;
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
        $this->validating = true;
        $this->validationResult = null;

        try {
            $validator = new ZipValidatorService();
            $this->validationResult = $validator->extractAndValidate($this->archive);

            if ($this->validationResult['valid']) {
                // Valid zip - Phase 4 will handle extraction
                Notification::make()
                    ->title('Validation Passed')
                    ->body('All '.count($this->validationResult['structure']).' folders contain the required files.')
                    ->success()
                    ->send();
            } else {
                // Invalid zip - show errors
                $errorCount = array_sum(array_map('count', $this->validationResult['errors']));
                Notification::make()
                    ->title('Validation Failed')
                    ->body("Found {$errorCount} issue(s) in ".count($this->validationResult['errors']).' folder(s). See details below.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Validation Error')
                ->body('Failed to validate zip: '.$e->getMessage())
                ->danger()
                ->send();
        }

        $this->validating = false;
    }
}
