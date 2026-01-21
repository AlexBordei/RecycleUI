# Laravel File Processing Architecture Research

## Document Intake/Validation System for Government Waste Transport Records

**Research Date:** 2026-01-21
**Domain:** Laravel + MySQL platform for zip file upload, validation, and extraction
**Target Paths:**
- Processing: `~/Desktop/SIATD/Processing/[User Full Name]/[datetime]/`
- History: `~/Desktop/SIATD/Done/`

---

## System Overview

```
+------------------+     +-------------------+     +--------------------+
|                  |     |                   |     |                    |
|   HTTP Request   |---->|   Controller      |---->|   Form Request     |
|   (ZIP Upload)   |     |   (Thin Layer)    |     |   (Validation)     |
|                  |     |                   |     |                    |
+------------------+     +--------+----------+     +---------+----------+
                                  |                          |
                                  v                          v
                         +--------+----------+     +---------+----------+
                         |                   |     |                    |
                         |   Service Class   |<----+   ZIP Validator    |
                         |   (Orchestrator)  |     |   (Custom Rule)    |
                         |                   |     |                    |
                         +--------+----------+     +--------------------+
                                  |
                    +-------------+-------------+
                    |             |             |
                    v             v             v
           +--------+--+  +-------+---+  +------+------+
           |           |  |           |  |             |
           |  Actions  |  |   Jobs    |  |  Pipeline   |
           | (Extract) |  | (Process) |  | (Validate)  |
           |           |  |           |  |             |
           +-----------+  +-----------+  +-------------+
                    |             |             |
                    +-------------+-------------+
                                  |
                                  v
                         +--------+----------+
                         |                   |
                         |  Storage Disks    |
                         |  (Local FS)       |
                         |                   |
                         |  - processing     |
                         |  - done           |
                         |  - temp           |
                         +-------------------+
```

---

## Component Responsibilities

| Component | Responsibility | Laravel Location |
|-----------|---------------|------------------|
| **Controller** | HTTP request handling, response formatting only | `app/Http/Controllers/` |
| **Form Request** | Input validation rules (file type, size, MIME) | `app/Http/Requests/` |
| **Custom Validation Rule** | ZIP content validation (9 files per folder) | `app/Rules/` |
| **Service Class** | Business logic orchestration, coordinates actions/jobs | `app/Services/` |
| **Action Classes** | Single-responsibility tasks (extract, validate, move) | `app/Actions/` |
| **Job Classes** | Background/queue processing for heavy operations | `app/Jobs/` |
| **Pipeline** | Sequential validation chain for folder contents | `app/Pipelines/` |
| **Repository** | Data access layer (upload history, user records) | `app/Repositories/` |
| **Events/Listeners** | Decoupled notifications, logging, cleanup | `app/Events/`, `app/Listeners/` |
| **Storage Disks** | Filesystem abstraction for external paths | `config/filesystems.php` |

---

## Recommended Project Structure

```
app/
├── Actions/
│   └── Document/
│       ├── ExtractZipAction.php           # Extract ZIP to temp
│       ├── ValidateFolderStructureAction.php  # Check 9 files per folder
│       ├── ValidateFileContentsAction.php     # Validate individual files
│       ├── MoveToProcessingAction.php         # Move to user's Processing folder
│       └── ArchiveToDoneAction.php            # Move completed to Done folder
│
├── Http/
│   ├── Controllers/
│   │   └── DocumentUploadController.php   # Thin controller
│   └── Requests/
│       └── UploadZipRequest.php           # Initial validation
│
├── Jobs/
│   ├── ProcessUploadedZipJob.php          # Main background job
│   └── CleanupTempFilesJob.php            # Cleanup after processing
│
├── Models/
│   ├── Upload.php                         # Upload history model
│   └── User.php                           # User model with full name
│
├── Pipelines/
│   └── DocumentValidation/
│       ├── ValidateZipIntegrity.php       # Step 1: ZIP is valid
│       ├── ValidateFolderCount.php        # Step 2: Expected folders
│       ├── ValidateFilesPerFolder.php     # Step 3: 9 files each
│       ├── ValidateFileTypes.php          # Step 4: Correct file types
│       └── ValidateFileContents.php       # Step 5: Content validation
│
├── Repositories/
│   ├── Interfaces/
│   │   └── UploadRepositoryInterface.php
│   └── UploadRepository.php               # Upload history data access
│
├── Rules/
│   └── ZipContainsValidStructure.php      # Custom ZIP validation rule
│
├── Services/
│   ├── Interfaces/
│   │   └── DocumentProcessingServiceInterface.php
│   └── DocumentProcessingService.php      # Main orchestrator
│
├── Events/
│   ├── ZipUploadStarted.php
│   ├── ZipValidationFailed.php
│   ├── ZipExtractionCompleted.php
│   └── DocumentProcessingCompleted.php
│
└── Listeners/
    ├── LogUploadActivity.php
    ├── NotifyUserOfFailure.php
    └── CleanupOnFailure.php

config/
└── filesystems.php                        # Custom disk configurations

database/
└── migrations/
    └── xxxx_create_uploads_table.php      # Upload history tracking
```

---

## Architectural Patterns

### 1. Service Layer Pattern (Primary Orchestrator)

The Service Layer acts as the central coordinator, keeping controllers thin and models focused on data.

```php
// app/Services/DocumentProcessingService.php

namespace App\Services;

use App\Actions\Document\ExtractZipAction;
use App\Actions\Document\MoveToProcessingAction;
use App\Jobs\ProcessUploadedZipJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Pipeline;

class DocumentProcessingService implements DocumentProcessingServiceInterface
{
    public function __construct(
        private ExtractZipAction $extractZip,
        private MoveToProcessingAction $moveToProcessing,
    ) {}

    public function processUpload(UploadedFile $file, User $user): ProcessingResult
    {
        // For immediate processing (small files)
        return $this->processSync($file, $user);

        // OR dispatch to queue (large files)
        // ProcessUploadedZipJob::dispatch($file->path(), $user->id);
    }

    private function processSync(UploadedFile $file, User $user): ProcessingResult
    {
        $tempPath = $this->extractZip->handle($file);

        $validationResult = Pipeline::send($tempPath)
            ->through([
                \App\Pipelines\DocumentValidation\ValidateFolderCount::class,
                \App\Pipelines\DocumentValidation\ValidateFilesPerFolder::class,
                \App\Pipelines\DocumentValidation\ValidateFileTypes::class,
            ])
            ->thenReturn();

        if ($validationResult->failed()) {
            return ProcessingResult::failure($validationResult->errors());
        }

        $destinationPath = $this->moveToProcessing->handle($tempPath, $user);

        return ProcessingResult::success($destinationPath);
    }
}
```

### 2. Action Pattern (Single Responsibility)

Each action handles one specific task, making code reusable and testable.

```php
// app/Actions/Document/ExtractZipAction.php

namespace App\Actions\Document;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExtractZipAction
{
    public function handle(UploadedFile $file): string
    {
        $tempDir = Storage::disk('temp')->path(uniqid('extract_'));

        $zip = new ZipArchive();
        $zip->open($file->getRealPath());
        $zip->extractTo($tempDir);
        $zip->close();

        return $tempDir;
    }
}
```

```php
// app/Actions/Document/MoveToProcessingAction.php

namespace App\Actions\Document;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class MoveToProcessingAction
{
    public function handle(string $sourcePath, User $user): string
    {
        $destinationPath = sprintf(
            '%s/%s',
            $user->full_name,
            now()->format('Y-m-d_H-i-s')
        );

        Storage::disk('processing')->makeDirectory($destinationPath);

        // Move all files from source to destination
        $this->moveRecursive($sourcePath, $destinationPath);

        return Storage::disk('processing')->path($destinationPath);
    }

    private function moveRecursive(string $source, string $destination): void
    {
        // Recursive directory move implementation
    }
}
```

### 3. Pipeline Pattern (Validation Chain)

Sequential validation steps with clear separation of concerns.

```php
// app/Pipelines/DocumentValidation/ValidateFilesPerFolder.php

namespace App\Pipelines\DocumentValidation;

use Closure;

class ValidateFilesPerFolder
{
    private const REQUIRED_FILES_PER_FOLDER = 9;

    public function handle(ValidationContext $context, Closure $next)
    {
        $folders = $this->getFolders($context->path);

        foreach ($folders as $folder) {
            $fileCount = count(glob($folder . '/*'));

            if ($fileCount !== self::REQUIRED_FILES_PER_FOLDER) {
                $context->addError(
                    "Folder '{$folder}' contains {$fileCount} files, expected " .
                    self::REQUIRED_FILES_PER_FOLDER
                );
            }
        }

        if ($context->hasErrors()) {
            return $context;
        }

        return $next($context);
    }

    private function getFolders(string $path): array
    {
        return array_filter(glob($path . '/*'), 'is_dir');
    }
}
```

### 4. Job Queue Pattern (Background Processing)

For large files, use queued jobs to prevent HTTP timeouts.

```php
// app/Jobs/ProcessUploadedZipJob.php

namespace App\Jobs;

use App\Models\User;
use App\Services\DocumentProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUploadedZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes

    public function __construct(
        private string $filePath,
        private int $userId,
        private int $uploadId,
    ) {}

    public function handle(DocumentProcessingService $service): void
    {
        $user = User::findOrFail($this->userId);

        $result = $service->processFromPath($this->filePath, $user);

        // Update upload record with result
        Upload::find($this->uploadId)->update([
            'status' => $result->success ? 'completed' : 'failed',
            'result_path' => $result->path,
            'errors' => $result->errors,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Upload::find($this->uploadId)->update([
            'status' => 'failed',
            'errors' => [$exception->getMessage()],
        ]);
    }
}
```

### 5. Custom Filesystem Disks (External Paths)

Configure disks for external paths outside Laravel's storage folder.

```php
// config/filesystems.php

'disks' => [
    // Default Laravel disks...

    'processing' => [
        'driver' => 'local',
        'root' => env('PROCESSING_PATH', '/Users/alexbordei/Desktop/SIATD/Processing'),
        'visibility' => 'private',
        'throw' => true,
    ],

    'done' => [
        'driver' => 'local',
        'root' => env('DONE_PATH', '/Users/alexbordei/Desktop/SIATD/Done'),
        'visibility' => 'private',
        'throw' => true,
    ],

    'temp' => [
        'driver' => 'local',
        'root' => storage_path('app/temp'),
        'visibility' => 'private',
    ],
],
```

```env
# .env
PROCESSING_PATH="/Users/alexbordei/Desktop/SIATD/Processing"
DONE_PATH="/Users/alexbordei/Desktop/SIATD/Done"
```

### 6. Custom Validation Rule (ZIP Contents)

```php
// app/Rules/ZipContainsValidStructure.php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use ZipArchive;

class ZipContainsValidStructure implements ValidationRule
{
    private const FILES_PER_FOLDER = 9;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $zip = new ZipArchive();

        if ($zip->open($value->getRealPath()) !== true) {
            $fail('The :attribute must be a valid ZIP file.');
            return;
        }

        $folders = $this->extractFolderStructure($zip);

        foreach ($folders as $folder => $files) {
            if (count($files) !== self::FILES_PER_FOLDER) {
                $fail("Folder '{$folder}' must contain exactly " . self::FILES_PER_FOLDER . " files.");
            }
        }

        $zip->close();
    }

    private function extractFolderStructure(ZipArchive $zip): array
    {
        $structure = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $parts = explode('/', $name);

            if (count($parts) > 1 && !str_ends_with($name, '/')) {
                $folder = $parts[0];
                $structure[$folder][] = $name;
            }
        }

        return $structure;
    }
}
```

---

## Data Flow Diagrams

### Upload Flow

```
User                Controller           FormRequest          Service
  |                     |                     |                   |
  |--POST /upload------>|                     |                   |
  |                     |--validate()-------->|                   |
  |                     |                     |--ZIP validation-->|
  |                     |                     |<--validation OK---|
  |                     |<--validated data----|                   |
  |                     |                     |                   |
  |                     |--processUpload()------------------->|   |
  |                     |                                     |   |
  |                     |                     +---------------+   |
  |                     |                     |                   |
  |                     |              ExtractZipAction           |
  |                     |              Pipeline Validation        |
  |                     |              MoveToProcessingAction     |
  |                     |                     |                   |
  |                     |<--ProcessingResult--+                   |
  |<--JSON response-----|                                         |
```

### Queue-Based Flow (Large Files)

```
Controller              Queue                 Worker               Storage
    |                     |                     |                     |
    |--dispatch job------>|                     |                     |
    |<--202 Accepted------|                     |                     |
    |                     |                     |                     |
    |                     |--pick up job------->|                     |
    |                     |                     |--extract ZIP------->|
    |                     |                     |--validate---------->|
    |                     |                     |--move files-------->|
    |                     |                     |                     |
    |                     |                     |--update DB--------->|
    |                     |<--job complete------|                     |
```

### Validation Pipeline Flow

```
ValidationContext
       |
       v
+------+------+     +------+------+     +------+------+
| ValidateZip |---->| ValidateFolder|-->| ValidateFiles|
| Integrity   |     | Count         |   | PerFolder    |
+-------------+     +---------------+   +--------------+
                                               |
       +---------------------------------------+
       |
       v
+------+------+     +------+------+
| ValidateFile|---->| ValidateFile|---> Result
| Types       |     | Contents    |
+-------------+     +-------------+
```

---

## Anti-Patterns to Avoid

| Anti-Pattern | Problem | Solution |
|--------------|---------|----------|
| **Fat Controllers** | Business logic in controllers makes code untestable | Move logic to Service classes and Actions |
| **Direct `env()` calls** | Returns `null` after `config:cache` in production | Always use `config()` helper, define values in config files |
| **Storing files in `public/`** | Security risk, direct web access to uploads | Use `storage/app/` with symlinks or external disks |
| **Original filenames** | Overwrites, path traversal attacks, script execution | Generate unique names with `Str::uuid()` or hashed names |
| **N+1 queries** | Fetching upload history without eager loading | Use `with()` for eager loading relationships |
| **Synchronous heavy processing** | HTTP timeouts, poor UX, resource blocking | Use queued jobs for files > 1MB or complex validation |
| **No cleanup on failure** | Orphaned temp files consume disk space | Use try/finally or event listeners for cleanup |
| **Hardcoded paths** | Deployment issues, environment-specific problems | Use config/filesystems.php with env variables |
| **Missing validation** | Malicious files, incorrect data | Layer validation: Form Request -> Custom Rule -> Pipeline |
| **No transaction wrapping** | Partial failures leave inconsistent state | Wrap DB operations in transactions |
| **Trusting ZIP contents** | Zip bombs, path traversal, malicious files | Validate before extraction, use whitelist for file types |
| **No progress tracking** | Users have no visibility into processing status | Store status in DB, provide polling endpoint or websockets |

---

## Configuration Examples

### Form Request with ZIP Validation

```php
// app/Http/Requests/UploadZipRequest.php

namespace App\Http\Requests;

use App\Rules\ZipContainsValidStructure;
use Illuminate\Foundation\Http\FormRequest;

class UploadZipRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:zip',
                'max:102400', // 100MB max
                new ZipContainsValidStructure(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a ZIP file to upload.',
            'file.mimes' => 'The file must be a ZIP archive.',
            'file.max' => 'The file size cannot exceed 100MB.',
        ];
    }
}
```

### Controller (Thin)

```php
// app/Http/Controllers/DocumentUploadController.php

namespace App\Http\Controllers;

use App\Http\Requests\UploadZipRequest;
use App\Services\DocumentProcessingServiceInterface;
use Illuminate\Http\JsonResponse;

class DocumentUploadController extends Controller
{
    public function __construct(
        private DocumentProcessingServiceInterface $processingService
    ) {}

    public function store(UploadZipRequest $request): JsonResponse
    {
        $result = $this->processingService->processUpload(
            $request->file('file'),
            $request->user()
        );

        if ($result->failed()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $result->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Documents processed successfully',
            'path' => $result->path(),
        ], 201);
    }
}
```

### Service Provider Registration

```php
// app/Providers/AppServiceProvider.php

public function register(): void
{
    $this->app->bind(
        DocumentProcessingServiceInterface::class,
        DocumentProcessingService::class
    );

    $this->app->bind(
        UploadRepositoryInterface::class,
        UploadRepository::class
    );
}
```

---

## Sources

### Primary Sources (High Confidence)

| Source | Topic | URL |
|--------|-------|-----|
| Laravel Documentation | File Storage & Filesystem | [laravel.com/docs/12.x/filesystem](https://laravel.com/docs/12.x/filesystem) |
| Laravel Documentation | Queues | [laravel.com/docs/12.x/queues](https://laravel.com/docs/12.x/queues) |
| Laravel Daily | Large CSV Import with Jobs | [laraveldaily.com](https://laraveldaily.com/post/laravel-import-very-large-csv-jobs-queues) |
| Laravel News | ZIP Content Validator | [laravel-news.com](https://laravel-news.com/laravel-zip-content-validator) |

### Secondary Sources (Medium-High Confidence)

| Source | Topic | URL |
|--------|-------|-----|
| Medium (Ratheepan) | Service-Action Architecture | [medium.com](https://ratheepan.medium.com/clean-service-action-architecture-a-battle-tested-pattern-for-laravel-applications-dc311ecc5c29) |
| Medium (Prevail) | Pipeline Pattern | [medium.com](https://medium.com/@prevailexcellent/understanding-laravel-pipeline-pattern-a-deep-dive-2023-346ed2123fb8) |
| DEV Community | Repository-Service Pattern | [dev.to](https://dev.to/blamsa0mine/structuring-a-laravel-project-with-the-repository-pattern-and-services-11pm) |
| Nabil Hassen | Action Pattern Best Practices | [nabilhassen.com](https://nabilhassen.com/action-pattern-in-laravel-concept-benefits-best-practices) |
| Lexo.ch | File Upload Best Practices | [lexo.ch](https://www.lexo.ch/blog/2025/08/file-upload-and-storage-in-laravel-best-practices/) |

### Community Sources (Medium Confidence)

| Source | Topic | URL |
|--------|-------|-----|
| GitHub (alexeymezenin) | Laravel Best Practices | [github.com](https://github.com/alexeymezenin/laravel-best-practices) |
| GitHub (orkhanahmadov) | ZIP Validator Package | [github.com](https://github.com/orkhanahmadov/laravel-zip-validator) |
| Binarcode | Laravel Anti-Patterns | [binarcode.com](https://www.binarcode.com/blog/common-antipaterns-laravel-development) |
| Benjamin Crozat | Architecture Best Practices | [benjamincrozat.com](https://benjamincrozat.com/laravel-architecture-best-practices) |
| Trovster | Custom Laravel Disks | [trovster.com](https://www.trovster.com/blog/2022/06/custom-laravel-disks-and-environment-based-filesystems) |

---

## Summary Recommendations for SIATD Project

1. **Use Service + Action pattern** - Service orchestrates, Actions handle single tasks
2. **Implement Pipeline for validation** - Chain of validators for 9-file folder requirement
3. **Configure custom disks** - `processing` and `done` disks pointing to external paths
4. **Queue large uploads** - Dispatch jobs for files > 5MB to prevent timeouts
5. **Layer validation** - Form Request (basic) -> Custom Rule (ZIP structure) -> Pipeline (contents)
6. **Track upload history** - Store status, paths, and errors in database for user visibility
7. **Event-driven cleanup** - Use events/listeners for logging and temp file cleanup
8. **Avoid anti-patterns** - Keep controllers thin, use config() not env(), unique filenames
