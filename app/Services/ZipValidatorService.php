<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ZipValidatorService
{
    /**
     * Required files for each folder (case-sensitive).
     */
    protected array $requiredFiles = [
        'ANEXA.pdf',
        'AVIZ.pdf',
        'Fata.jpeg',
        'Inc1.jpeg',
        'Inc2.jpeg',
        'Km.jpeg',
        'Lateral.jpeg',
        'Spate.jpeg',
    ];

    /**
     * Excel filename pattern (regex).
     * Matches: Iesiri_export_robotel_siatd_intern_DD_MM_YYYY_HH_MM_SS.xlsx
     */
    protected string $excelPattern = '/^Iesiri_export_robotel_siatd_intern_\d{2}_\d{2}_\d{4}_\d{2}_\d{2}_\d{2}\.xlsx$/i';

    /**
     * Temporary extraction path.
     */
    protected ?string $tempPath = null;

    /**
     * Validation errors collected during validation.
     */
    protected array $errors = [];

    /**
     * Folder structure from the zip (for preview).
     */
    protected array $structure = [];

    /**
     * Clean up temporary files on destruction.
     */
    public function __destruct()
    {
        $this->cleanup();
    }

    /**
     * Extract zip to temporary directory with security checks.
     *
     * @throws \RuntimeException If extraction fails or Zip Slip detected
     */
    public function extract(UploadedFile $file): string
    {
        $zip = new ZipArchive();
        $result = $zip->open($file->getRealPath());

        if ($result !== true) {
            throw new \RuntimeException('Failed to open zip file: '.$this->getZipError($result));
        }

        // Create temp directory
        $this->tempPath = storage_path('app/temp/'.uniqid('zip_', true));
        File::makeDirectory($this->tempPath, 0755, true);

        // Extract with Zip Slip prevention
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Skip macOS metadata files
            if (str_starts_with($filename, '__MACOSX/') || str_contains($filename, '.DS_Store')) {
                continue;
            }

            // Zip Slip prevention: check for path traversal attempts
            if (str_contains($filename, '..') || str_starts_with($filename, '/')) {
                $this->cleanup();
                throw new \RuntimeException('Zip Slip attack detected: '.$filename);
            }

            // Normalize the path and ensure it stays within temp directory
            $targetPath = $this->tempPath.'/'.ltrim($filename, '/');
            $realBase = realpath($this->tempPath);

            // Create directory if it doesn't exist
            $targetDir = dirname($targetPath);
            if (! File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            // Verify the target stays within the temp directory
            $realTarget = realpath($targetDir);
            if ($realTarget === false || ! str_starts_with($realTarget, $realBase)) {
                $this->cleanup();
                throw new \RuntimeException('Zip Slip attack detected: '.$filename);
            }

            $zip->extractTo($this->tempPath, $filename);
        }

        $zip->close();

        return $this->tempPath;
    }

    /**
     * Clean up temporary extraction directory.
     */
    public function cleanup(): void
    {
        if ($this->tempPath && File::isDirectory($this->tempPath)) {
            File::deleteDirectory($this->tempPath);
            $this->tempPath = null;
        }
    }

    /**
     * Get the temporary extraction path.
     */
    public function getTempPath(): ?string
    {
        return $this->tempPath;
    }

    /**
     * Validate the extracted zip contents.
     *
     * @return array{valid: bool, errors: array, structure: array}
     */
    public function validate(): array
    {
        if (! $this->tempPath || ! File::isDirectory($this->tempPath)) {
            throw new \RuntimeException('No extracted content to validate. Call extract() first.');
        }

        $this->errors = [];
        $this->structure = [];

        // Get all top-level directories (transport record folders)
        $folders = File::directories($this->tempPath);

        if (empty($folders)) {
            $this->errors['_root'] = ['Zip archive contains no folders. Expected folders with transport records.'];

            return $this->getResult();
        }

        foreach ($folders as $folder) {
            $this->validateFolder($folder);
        }

        return $this->getResult();
    }

    /**
     * Validate a single folder against the required schema.
     */
    protected function validateFolder(string $folderPath): void
    {
        $folderName = basename($folderPath);
        $files = File::files($folderPath);
        $fileNames = array_map(fn ($f) => $f->getFilename(), $files);

        $this->structure[$folderName] = [
            'files' => $fileNames,
            'valid' => true,
            'missing' => [],
            'excel' => null,
        ];

        $folderErrors = [];

        // Check each required file
        foreach ($this->requiredFiles as $requiredFile) {
            if (! in_array($requiredFile, $fileNames, true)) {
                $folderErrors[] = "Missing required file: {$requiredFile}";
                $this->structure[$folderName]['missing'][] = $requiredFile;
            }
        }

        // Check for Excel file matching pattern
        $excelFound = false;
        foreach ($fileNames as $fileName) {
            if (preg_match($this->excelPattern, $fileName)) {
                $excelFound = true;
                $this->structure[$folderName]['excel'] = $fileName;
                break;
            }
        }

        if (! $excelFound) {
            $folderErrors[] = 'Missing Excel file matching pattern: Iesiri_export_robotel_siatd_intern_DD_MM_YYYY_HH_MM_SS.xlsx';
            $this->structure[$folderName]['missing'][] = 'Excel (Iesiri_export_robotel_siatd_intern_*.xlsx)';
        }

        if (! empty($folderErrors)) {
            $this->errors[$folderName] = $folderErrors;
            $this->structure[$folderName]['valid'] = false;
        }
    }

    /**
     * Get the validation result.
     *
     * @return array{valid: bool, errors: array, structure: array}
     */
    protected function getResult(): array
    {
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'structure' => $this->structure,
        ];
    }

    /**
     * Get just the structure for preview (without full validation).
     */
    public function getStructure(): array
    {
        if (! $this->tempPath || ! File::isDirectory($this->tempPath)) {
            throw new \RuntimeException('No extracted content. Call extract() first.');
        }

        $structure = [];
        $folders = File::directories($this->tempPath);

        foreach ($folders as $folder) {
            $folderName = basename($folder);
            $files = File::files($folder);
            $structure[$folderName] = array_map(fn ($f) => $f->getFilename(), $files);
        }

        return $structure;
    }

    /**
     * Convenience method to extract and validate in one call.
     *
     * @return array{valid: bool, errors: array, structure: array}
     */
    public function extractAndValidate(UploadedFile $file): array
    {
        $this->extract($file);

        return $this->validate();
    }

    /**
     * Get the validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get human-readable error message for ZipArchive error codes.
     */
    protected function getZipError(int $code): string
    {
        $errors = [
            ZipArchive::ER_EXISTS => 'File already exists',
            ZipArchive::ER_INCONS => 'Zip archive inconsistent',
            ZipArchive::ER_INVAL => 'Invalid argument',
            ZipArchive::ER_MEMORY => 'Memory allocation failure',
            ZipArchive::ER_NOENT => 'No such file',
            ZipArchive::ER_NOZIP => 'Not a zip archive',
            ZipArchive::ER_OPEN => 'Cannot open file',
            ZipArchive::ER_READ => 'Read error',
            ZipArchive::ER_SEEK => 'Seek error',
        ];

        return $errors[$code] ?? 'Unknown error (code: '.$code.')';
    }
}
