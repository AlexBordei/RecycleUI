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
