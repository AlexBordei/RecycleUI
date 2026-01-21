<?php

namespace App\Filament\Pages;

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
     * Remove the currently selected file.
     */
    public function removeFile(): void
    {
        $this->archive = null;
    }
}
