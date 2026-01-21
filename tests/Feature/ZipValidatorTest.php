<?php

namespace Tests\Feature;

use App\Services\ZipValidatorService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

class ZipValidatorTest extends TestCase
{
    protected ZipValidatorService $validator;

    protected string $testZipPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ZipValidatorService;
        $this->testZipPath = storage_path('app/test-zips');
        File::makeDirectory($this->testZipPath, 0755, true, true);
    }

    protected function tearDown(): void
    {
        // Clean up test zip directory
        if (File::isDirectory($this->testZipPath)) {
            File::deleteDirectory($this->testZipPath);
        }
        parent::tearDown();
    }

    /**
     * Create a test zip file with specified structure.
     *
     * @param  array  $folders  Array of folder => files, or special keys like '_root_files' for root-level files
     */
    protected function createTestZip(array $folders): UploadedFile
    {
        $zipPath = $this->testZipPath.'/'.uniqid('test_').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);

        // If folders array is empty, add a root-level file to ensure zip is created
        if (empty($folders)) {
            $zip->addFromString('readme.txt', 'Empty archive placeholder');
        } else {
            foreach ($folders as $folderName => $files) {
                // Special handling for root-level files
                if ($folderName === '_root_files') {
                    foreach ($files as $fileName) {
                        $zip->addFromString($fileName, 'test content');
                    }

                    continue;
                }

                // Add empty directory if no files
                if (empty($files)) {
                    $zip->addEmptyDir($folderName);
                } else {
                    foreach ($files as $fileName) {
                        $zip->addFromString("{$folderName}/{$fileName}", 'test content');
                    }
                }
            }
        }

        $zip->close();

        return new UploadedFile(
            $zipPath,
            'test.zip',
            'application/zip',
            null,
            true
        );
    }

    /**
     * Get complete file list for a valid folder.
     */
    protected function getCompleteFileList(string $excelTimestamp = '21_01_2026_10_30_00'): array
    {
        return [
            'ANEXA.pdf',
            'AVIZ.pdf',
            'Fata.jpeg',
            'Inc1.jpeg',
            'Inc2.jpeg',
            'Km.jpeg',
            'Lateral.jpeg',
            'Spate.jpeg',
            "Iesiri_export_robotel_siatd_intern_{$excelTimestamp}.xlsx",
        ];
    }

    #[Test]
    public function it_validates_complete_folder_successfully(): void
    {
        $file = $this->createTestZip([
            'Transport001' => $this->getCompleteFileList(),
        ]);

        $result = $this->validator->extractAndValidate($file);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertArrayHasKey('Transport001', $result['structure']);
        $this->assertTrue($result['structure']['Transport001']['valid']);
    }

    #[Test]
    public function it_validates_multiple_complete_folders(): void
    {
        $file = $this->createTestZip([
            'Transport001' => $this->getCompleteFileList('21_01_2026_10_30_00'),
            'Transport002' => $this->getCompleteFileList('21_01_2026_11_30_00'),
            'Transport003' => $this->getCompleteFileList('21_01_2026_12_30_00'),
        ]);

        $result = $this->validator->extractAndValidate($file);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertCount(3, $result['structure']);
    }

    #[Test]
    public function it_detects_missing_required_files(): void
    {
        $file = $this->createTestZip([
            'Transport001' => [
                'ANEXA.pdf',
                'AVIZ.pdf',
                // Missing: Fata.jpeg, Inc1.jpeg, Inc2.jpeg, Km.jpeg, Lateral.jpeg, Spate.jpeg
                'Iesiri_export_robotel_siatd_intern_21_01_2026_10_30_00.xlsx',
            ],
        ]);

        $result = $this->validator->extractAndValidate($file);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('Transport001', $result['errors']);
        $this->assertCount(6, $result['errors']['Transport001']); // 6 missing files
        $this->assertFalse($result['structure']['Transport001']['valid']);
        $this->assertCount(6, $result['structure']['Transport001']['missing']);
    }

    #[Test]
    public function it_detects_missing_excel_file(): void
    {
        $file = $this->createTestZip([
            'Transport001' => [
                'ANEXA.pdf',
                'AVIZ.pdf',
                'Fata.jpeg',
                'Inc1.jpeg',
                'Inc2.jpeg',
                'Km.jpeg',
                'Lateral.jpeg',
                'Spate.jpeg',
                // Missing Excel file
            ],
        ]);

        $result = $this->validator->extractAndValidate($file);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('Transport001', $result['errors']);
        $this->assertCount(1, $result['errors']['Transport001']);
        $this->assertStringContainsString('Excel', $result['errors']['Transport001'][0]);
    }

    #[Test]
    public function it_validates_excel_filename_pattern_correctly(): void
    {
        // Valid pattern
        $file = $this->createTestZip([
            'Transport001' => $this->getCompleteFileList('21_01_2026_10_30_00'),
        ]);

        $result = $this->validator->extractAndValidate($file);
        $this->assertTrue($result['valid']);
        $this->assertEquals(
            'Iesiri_export_robotel_siatd_intern_21_01_2026_10_30_00.xlsx',
            $result['structure']['Transport001']['excel']
        );
    }

    #[Test]
    public function it_rejects_invalid_excel_filename_pattern(): void
    {
        // Create new validator instance for clean state
        $this->validator = new ZipValidatorService;

        $file = $this->createTestZip([
            'Transport001' => [
                'ANEXA.pdf',
                'AVIZ.pdf',
                'Fata.jpeg',
                'Inc1.jpeg',
                'Inc2.jpeg',
                'Km.jpeg',
                'Lateral.jpeg',
                'Spate.jpeg',
                'random_excel_file.xlsx', // Wrong pattern
            ],
        ]);

        $result = $this->validator->extractAndValidate($file);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('Transport001', $result['errors']);
        $this->assertStringContainsString('Excel', $result['errors']['Transport001'][0]);
        $this->assertNull($result['structure']['Transport001']['excel']);
    }

    #[Test]
    public function it_validates_multiple_folders_with_mixed_results(): void
    {
        $file = $this->createTestZip([
            'Transport001' => $this->getCompleteFileList(), // Valid
            'Transport002' => [
                'ANEXA.pdf', // Missing other files
            ],
        ]);

        $result = $this->validator->extractAndValidate($file);

        $this->assertFalse($result['valid']);
        $this->assertArrayNotHasKey('Transport001', $result['errors']); // Valid folder
        $this->assertArrayHasKey('Transport002', $result['errors']); // Invalid folder
        $this->assertTrue($result['structure']['Transport001']['valid']);
        $this->assertFalse($result['structure']['Transport002']['valid']);
    }

    #[Test]
    public function it_rejects_empty_zip(): void
    {
        $file = $this->createTestZip([]);

        $result = $this->validator->extractAndValidate($file);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('_root', $result['errors']);
        $this->assertStringContainsString('no folders', $result['errors']['_root'][0]);
    }

    #[Test]
    public function it_provides_structure_for_preview(): void
    {
        $file = $this->createTestZip([
            'Folder1' => ['file1.txt', 'file2.pdf'],
            'Folder2' => ['file3.xlsx'],
        ]);

        $this->validator->extract($file);
        $structure = $this->validator->getStructure();

        $this->assertArrayHasKey('Folder1', $structure);
        $this->assertArrayHasKey('Folder2', $structure);
        $this->assertCount(2, $structure['Folder1']);
        $this->assertCount(1, $structure['Folder2']);
        $this->assertContains('file1.txt', $structure['Folder1']);
        $this->assertContains('file2.pdf', $structure['Folder1']);
        $this->assertContains('file3.xlsx', $structure['Folder2']);
    }

    #[Test]
    public function it_cleans_up_temp_files(): void
    {
        $file = $this->createTestZip([
            'Folder1' => ['file1.txt'],
        ]);

        $tempPath = $this->validator->extract($file);
        $this->assertTrue(File::isDirectory($tempPath));

        $this->validator->cleanup();
        $this->assertFalse(File::isDirectory($tempPath));
    }

    #[Test]
    public function it_returns_temp_path(): void
    {
        $file = $this->createTestZip([
            'Folder1' => ['file1.txt'],
        ]);

        $this->assertNull($this->validator->getTempPath());

        $tempPath = $this->validator->extract($file);

        $this->assertEquals($tempPath, $this->validator->getTempPath());
        $this->assertStringContainsString('zip_', $tempPath);
    }

    #[Test]
    public function it_throws_exception_when_validating_without_extraction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No extracted content to validate');

        $this->validator->validate();
    }

    #[Test]
    public function it_throws_exception_when_getting_structure_without_extraction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No extracted content');

        $this->validator->getStructure();
    }

    #[Test]
    public function it_detects_case_sensitive_file_names(): void
    {
        $file = $this->createTestZip([
            'Transport001' => [
                'anexa.pdf', // Wrong case - should be ANEXA.pdf
                'AVIZ.pdf',
                'Fata.jpeg',
                'Inc1.jpeg',
                'Inc2.jpeg',
                'Km.jpeg',
                'Lateral.jpeg',
                'Spate.jpeg',
                'Iesiri_export_robotel_siatd_intern_21_01_2026_10_30_00.xlsx',
            ],
        ]);

        $result = $this->validator->extractAndValidate($file);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('Transport001', $result['errors']);
        // Should report ANEXA.pdf as missing (case mismatch)
        $this->assertStringContainsString('ANEXA.pdf', $result['errors']['Transport001'][0]);
    }

    #[Test]
    public function it_tracks_which_files_are_missing_in_structure(): void
    {
        $file = $this->createTestZip([
            'Transport001' => [
                'ANEXA.pdf',
                'AVIZ.pdf',
                // Missing all image files and Excel
            ],
        ]);

        $result = $this->validator->extractAndValidate($file);

        $missing = $result['structure']['Transport001']['missing'];
        $this->assertContains('Fata.jpeg', $missing);
        $this->assertContains('Inc1.jpeg', $missing);
        $this->assertContains('Inc2.jpeg', $missing);
        $this->assertContains('Km.jpeg', $missing);
        $this->assertContains('Lateral.jpeg', $missing);
        $this->assertContains('Spate.jpeg', $missing);
    }

    #[Test]
    public function it_stores_found_excel_filename_in_structure(): void
    {
        $file = $this->createTestZip([
            'Transport001' => $this->getCompleteFileList('15_06_2025_09_15_30'),
        ]);

        $result = $this->validator->extractAndValidate($file);

        $this->assertEquals(
            'Iesiri_export_robotel_siatd_intern_15_06_2025_09_15_30.xlsx',
            $result['structure']['Transport001']['excel']
        );
    }

    #[Test]
    public function it_extracts_zip_successfully(): void
    {
        $file = $this->createTestZip([
            'TestFolder' => ['test.txt'],
        ]);

        $tempPath = $this->validator->extract($file);

        $this->assertDirectoryExists($tempPath);
        $this->assertDirectoryExists($tempPath.'/TestFolder');
        $this->assertFileExists($tempPath.'/TestFolder/test.txt');
    }
}
