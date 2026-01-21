<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Upload extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Upload Archive';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Upload Zip Archive';

    protected static string $view = 'filament.pages.upload';
}
