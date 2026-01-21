# Technology Stack Research: Document Intake/Validation System

**Research Date:** January 21, 2026
**Domain:** Government waste transport records validation
**Framework:** Laravel + MySQL

---

## Executive Summary

For a Laravel-based document intake system processing zip files containing transport documentation (PDFs, images, Excel files), the recommended stack centers on **Filament v3** for admin panel, **native PHP ZipArchive** with validation wrappers, **Spatie Media Library** for file management, and **Maatwebsite Excel** for spreadsheet processing.

---

## Core Technologies

| Package | Version | Purpose | Why Recommended |
|---------|---------|---------|-----------------|
| **filament/filament** | ^3.2 | Admin panel & CRUD | Free, Livewire-based, modular design, excellent plugin ecosystem. Better for budget-conscious projects vs Nova ($99/project). |
| **PHP ZipArchive** | Built-in | Zip extraction | Native PHP class (since PHP 5.2), no external dependencies, full control over extraction. Use with validation wrapper. |
| **orkhanahmadov/laravel-zip-validator** | ^2.0 | Zip content validation | Purpose-built for validating zip contents before extraction. Validates required files, file sizes, prevents Zip Slip attacks. |
| **spatie/laravel-medialibrary** | ^11.0 | File/media management | 11M+ downloads, associates files with Eloquent models, handles thumbnails, works with any filesystem (local, S3). |
| **maatwebsite/excel** | ^3.1.67 | Excel import/validation | 142M+ downloads, 12 years maintained, supports Laravel 5.8-12, handles large files via chunking. |
| **spatie/livewire-filepond** | ^1.5 | File upload UI | Modern drag-drop uploads, integrates with Livewire/Filament, progress indicators, multiple file support. |

---

## Supporting Libraries

| Package | Version | Purpose | Notes |
|---------|---------|---------|-------|
| **filament/spatie-laravel-media-library-plugin** | ^3.2 | Filament + Media Library integration | Official plugin for seamless media management in Filament admin |
| **spatie/laravel-image-optimizer** | ^1.8 | Image optimization | Compress uploaded images automatically |
| **intervention/image** | ^3.0 | Image manipulation | Resize, crop, validate image dimensions |
| **eightynine/filament-approvals** | ^3.0 | Approval workflows | Multi-step approval flows for document validation |

---

## Installation Commands

```bash
# Core admin panel
composer require filament/filament:"^3.2" -W
php artisan filament:install --panels

# Zip validation (use alongside native ZipArchive)
composer require orkhanahmadov/laravel-zip-validator

# Media/file management
composer require spatie/laravel-medialibrary
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate

# Filament Media Library plugin
composer require filament/spatie-laravel-media-library-plugin:"^3.2" -W

# Excel processing
composer require maatwebsite/excel
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config

# File upload UI (for Livewire components)
composer require spatie/livewire-filepond
php artisan vendor:publish --tag="livewire-filepond-views"

# Image optimization (optional but recommended)
composer require spatie/laravel-image-optimizer
composer require intervention/image

# Approval workflows (if needed)
composer require eightynine/filament-approvals
```

---

## Architecture Recommendations

### Zip File Processing Flow

```php
// 1. Validate zip contents BEFORE extraction
use Orkhanahmadov\ZipValidator\Rules\ZipContent;

$request->validate([
    'transport_zip' => [
        'required',
        'file',
        'mimes:zip',
        'max:102400', // 100MB max
        new ZipContent([
            'ANEXA.pdf',
            'AVIZ.pdf',
            '*.xlsx',     // Excel file
            '*.jpg',      // JPEG images
        ], [
            // Max size validation per file type
            'ANEXA.pdf' => 10240,  // 10MB
            'AVIZ.pdf' => 10240,
            '*.xlsx' => 5120,      // 5MB
            '*.jpg' => 2048,       // 2MB per image
        ]),
    ],
]);

// 2. Extract using native ZipArchive with security
$zip = new ZipArchive();
if ($zip->open($filePath) === true) {
    // Validate each entry before extraction (prevent Zip Slip)
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        if (strpos($filename, '..') !== false) {
            throw new SecurityException('Invalid path in zip');
        }
    }
    $zip->extractTo($destinationPath);
    $zip->close();
}
```

### File Validation Strategy

```php
// Custom validation for transport documentation folder structure
class TransportDocumentValidator
{
    private const REQUIRED_FILES = [
        'ANEXA.pdf' => ['mimes' => 'pdf', 'max' => 10240],
        'AVIZ.pdf' => ['mimes' => 'pdf', 'max' => 10240],
    ];

    private const REQUIRED_IMAGES = 6; // 6 JPEG images
    private const EXCEL_PATTERN = '/^[A-Z]{2,3}\d{2,3}[A-Z]{3}_\d{8}\.xlsx$/'; // e.g., AB12XYZ_20260121.xlsx
}
```

### Filament Resource Example

```php
// App/Filament/Resources/TransportRecordResource.php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class TransportRecordResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form->schema([
            SpatieMediaLibraryFileUpload::make('documents')
                ->collection('transport-docs')
                ->multiple()
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                ->maxSize(102400),
        ]);
    }
}
```

---

## Alternatives Considered

### Admin Panels

| Option | Verdict | Reason |
|--------|---------|--------|
| **Laravel Nova** | Not recommended | $99/project cost adds up, Vue.js dependency when Livewire is preferred for this stack |
| **Backpack for Laravel** | Alternative | More customization but steeper learning curve, commercial license for Pro features |
| **Voyager** | Not recommended | Visual builder approach, less suited for complex validation workflows |

### Zip Handling

| Option | Verdict | Reason |
|--------|---------|--------|
| **madnest/madzipper** | Viable alternative | Good extraction controls (whitelist/blacklist), but orkhanahmadov/laravel-zip-validator has better pre-extraction validation |
| **zanysoft/laravel-zip** | Not recommended | Less maintained, fewer security features |
| **motekar/laravel-zip** | Viable alternative | Simpler API, good for basic needs |

### File Management

| Option | Verdict | Reason |
|--------|---------|--------|
| **Laravel Storage (native)** | Baseline | Good for simple cases, but lacks media associations and conversions |
| **codedor/filament-media-library** | Alternative | Good Filament integration but less community support than Spatie |

---

## What NOT to Use

| Package/Approach | Reason |
|------------------|--------|
| **dariusiii/zipper** | Known Zip Slip vulnerability (CVE). Path traversal during extraction allows writing files outside target directory. |
| **Storing files in `public/`** | Security risk - direct access to uploaded files, potential for executable PHP uploads |
| **Client-side only validation** | Never trust browser validation alone. Always validate server-side. |
| **File extension-only validation** | Attackers rename `.php` to `.jpg`. Always validate MIME types AND content. |
| **livewire/livewire < v3.5.2** | CVE: File extension guessed from MIME type only, allowing `.php` extension bypass |
| **Hardcoded file paths** | Use Laravel Storage facade for portability across environments |
| **.phar file uploads** | Not blocked by default Laravel validation. Executes as PHP on Debian-based systems. |

---

## Security Checklist for File Uploads

- [ ] Validate MIME types server-side (not just extensions)
- [ ] Check for `.phar` files explicitly
- [ ] Use random filenames (never trust user-provided names)
- [ ] Store uploads outside web root or use Storage facade
- [ ] Validate zip contents BEFORE extraction
- [ ] Check for path traversal (`../`) in zip entries
- [ ] Set max file size limits per file type
- [ ] Disable PHP execution in upload directories
- [ ] Use dedicated storage disk, preferably S3 or similar

---

## Sources

### High Confidence (Official docs, major publications)

- [Filament PHP Official Documentation](https://filamentphp.com/docs/3.x/forms/fields/file-upload) - File upload and validation
- [Spatie Laravel Media Library v11 Docs](https://spatie.be/docs/laravel-medialibrary/v11/introduction) - File associations
- [Laravel Excel Official Site](https://laravel-excel.com) - Excel import/export (142M downloads, 12 years maintained)
- [Filament Spatie Media Library Plugin](https://filamentphp.com/plugins/filament-spatie-media-library) - Official integration
- [spatie/livewire-filepond on Packagist](https://packagist.org/packages/spatie/livewire-filepond) - v1.5.0, PHP 8.2+, Laravel 10-12

### Medium Confidence (Community comparisons, tutorials)

- [Arsenal Tech - Filament vs Nova CTO Guide 2025](https://arsenaltech.com/blog/filament-vs-laravel-nova-cto-guide) - Detailed comparison
- [Backpack for Laravel - 2025 Admin Panel Alternatives](https://backpackforlaravel.com/articles/opinions/backpack-laravel-alternatives-2025-top-admin-panels-and-frameworks)
- [Laravel News - Zip Content Validator](https://laravel-news.com/laravel-zip-content-validator) - Package announcement
- [Orkhan's Blog - Validating ZIP Content](https://orkhan.dev/2020/01/13/validating-zip-file-content-in-laravel/) - Implementation guide
- [Filament Approvals Plugin](https://filamentphp.com/plugins/eightynine-approvals) - Workflow management

### Medium Confidence (Security advisories)

- [Snyk - Zip Slip in dariusiii/zipper](https://security.snyk.io/vuln/SNYK-PHP-DARIUSIIIZIPPER-552163) - CVE details
- [Medium - Prevent Malware File Uploads](https://medium.com/@rsharifur/how-to-prevent-malware-file-uploads-in-php-laravel-projects-a-web-developers-guide-0f9bf247554f) - Security practices
- [CVE Details - Laravel Vulnerabilities](https://www.cvedetails.com/vulnerability-list/vendor_id-16542/product_id-38139/Laravel-Laravel.html) - Including .phar and Livewire issues

### Low Confidence (Forum discussions, older posts)

- [Laracasts Discussion - Extract ZIP and Select Files](https://laracasts.com/discuss/channels/laravel/extract-zip-file-and-select-png-files)
- [GitHub - orkhanahmadov/laravel-zip-validator](https://github.com/orkhanahmadov/laravel-zip-validator) - Repository docs

---

## Version Compatibility Matrix

| Package | PHP | Laravel | Notes |
|---------|-----|---------|-------|
| filament/filament 3.x | 8.1+ | 10, 11, 12 | Requires Livewire 3 |
| spatie/laravel-medialibrary 11.x | 8.2+ | 10, 11, 12 | |
| maatwebsite/excel 3.1.x | 7.0+, 8.0+ | 5.8 - 12 | Wide compatibility |
| spatie/livewire-filepond 1.5.x | 8.2+ | 10, 11, 12 | Requires Livewire 3.5+ |
| orkhanahmadov/laravel-zip-validator 2.x | 8.0+ | 9, 10, 11 | Check for Laravel 12 support |

---

## Next Steps

1. Initialize Laravel 12 project with Filament v3
2. Configure Spatie Media Library with local disk for development, S3 for production
3. Create custom validation rules for transport document folder structure
4. Build Filament resources for document upload and validation workflow
5. Implement zip processing queue job with security validation
