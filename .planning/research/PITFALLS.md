# Laravel File Upload Pitfalls Research

> Research compiled: 2026-01-21
> Domain: Document intake/validation system for government waste transport records
> Context: Laravel platform handling zip uploads with multiple folders, validating 9 files per folder, extracting to local paths with spaces in names (user full names), handling Excel filename pattern matching

---

## Critical Pitfalls

### 1. Memory Exhaustion on Large File Uploads

**What Goes Wrong:**
When users upload large files (e.g., 200MB+ zip archives), PHP loads the entire file into memory during processing. If ten users upload simultaneously, server RAM is quickly exhausted, causing crashes or 500 errors.

**Why It Happens:**
- PHP's default `memory_limit` is often 128MB-256MB
- Laravel's `FilesystemAdapter` uses `fpassthru()` which loads entire files into memory
- Developers test with small files locally but production sees larger payloads
- No chunking strategy implemented for file processing

**How to Avoid:**
- Implement chunked uploads for files over 50MB using libraries like Dropzone.js or Resumable.js
- Configure PHP limits appropriately: `upload_max_filesize`, `post_max_size`, `memory_limit`
- Use streaming reads instead of loading entire files: `Storage::readStream()` instead of `Storage::get()`
- Process large files via queue jobs rather than synchronous HTTP requests
- For zip extraction, extract file-by-file rather than all at once

**Warning Signs:**
- "Allowed memory size of X bytes exhausted" errors
- Server becoming unresponsive during uploads
- Timeouts during file processing
- Works locally but fails in production

**Phase to Address:** Architecture/Planning (before any file handling code is written)

---

### 2. Zip Slip (Path Traversal) Vulnerability

**What Goes Wrong:**
Malicious zip archives contain files with path traversal sequences like `../../etc/passwd` or `../../../var/www/html/shell.php`. When extracted without validation, these files write outside the intended directory, potentially overwriting system files or placing executable code in web-accessible locations.

**Why It Happens:**
- PHP's `ZipArchive::extractTo()` does not sanitize filenames
- Developers trust uploaded content implicitly
- Popular Laravel zip packages (chumper/zipper, dariusiii/zipper, madnest/madzipper) have had this vulnerability
- No validation of extracted file paths against target directory

**How to Avoid:**
```php
// ALWAYS validate extracted paths
$targetDir = realpath($extractPath);
foreach ($zip->listContents() as $file) {
    $fullPath = realpath($extractPath . '/' . $file['path']);
    if (strpos($fullPath, $targetDir) !== 0) {
        throw new SecurityException("Path traversal detected: {$file['path']}");
    }
}
```
- Use updated packages: madzipper >= 2.0.2, laravel-filemanager >= 2.6.4
- Never extract directly to web-accessible directories
- Run `composer audit` regularly to detect vulnerable dependencies

**Warning Signs:**
- Using old versions of zip packages
- Direct extraction without path validation
- Files appearing in unexpected directories
- No security audit of file handling code

**Phase to Address:** Security Review (before any zip handling implementation)

---

### 3. Zip Bomb (Decompression Bomb) Attack

**What Goes Wrong:**
A 42KB zip file can decompress to 4.7 petabytes (4,718,592 GB), exhausting disk space and causing denial of service. Even less extreme cases (1MB -> 1GB) can fill up server storage.

**Why It Happens:**
- No validation of decompressed size before extraction
- Zip format allows extreme compression ratios
- Developers only validate compressed file size, not decompressed
- No disk space monitoring or limits

**How to Avoid:**
- Check decompressed size before extraction using `$zip->statIndex()` to sum uncompressed sizes
- Set reasonable limits (e.g., max 100MB decompressed for your use case)
- Monitor disk space and alert at thresholds
- Extract to a separate partition with quotas
- Use Laravel Enlightn's file bomb validation analyzer

```php
// Check total uncompressed size before extraction
$zip = new ZipArchive();
$zip->open($file);
$totalSize = 0;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $totalSize += $zip->statIndex($i)['size'];
    if ($totalSize > $maxAllowedSize) {
        throw new ValidationException("Archive too large when decompressed");
    }
}
```

**Warning Signs:**
- No size validation on zip contents
- Disk space issues after processing uploads
- Very small uploads causing very long processing times

**Phase to Address:** Validation Implementation (when building upload handling)

---

### 4. File Validation Bypass (CVE-2025-27515)

**What Goes Wrong:**
When using wildcard validation for file arrays (`files.*`), attackers can craft malicious requests that bypass validation rules entirely, allowing upload of dangerous file types.

**Why It Happens:**
- Laravel versions before 10.48.29, 11.44.1, and 12.1.1 have a vulnerability in array key processing
- Wildcard validation doesn't properly handle all request structures
- Developers rely solely on Laravel validation without defense in depth

**How to Avoid:**
- Update Laravel to patched versions (11.44.1+, 12.1.1+)
- Validate both the array and individual elements:
```php
$rules = [
    'files' => ['required', 'array', 'max:9'],
    'files.*' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
];
```
- Implement server-side MIME type verification using magic bytes
- Never trust client-provided extensions or MIME types
- Store files with generated names, not user-provided names

**Warning Signs:**
- Using only wildcard validation without array-level rules
- Accepting user-provided filenames for storage
- Not validating actual file content (magic bytes)
- Running outdated Laravel version

**Phase to Address:** Before Production (security hardening phase)

---

### 5. MIME Type Validation Bypass with Double Extensions

**What Goes Wrong:**
Files like `malicious.php.jpg` or `shell.phar` pass MIME validation but execute as code. Attackers prepend valid file signatures (e.g., GIF87a) to malicious files to pass MIME checks.

**Why It Happens:**
- Laravel validates MIME type based on file content magic bytes
- User-provided extension is trusted for final filename
- `getClientOriginalExtension()` returns attacker-controlled data
- Web servers may execute files based on certain extensions

**How to Avoid:**
- NEVER use `getClientOriginalName()` or `getClientOriginalExtension()` for stored filenames
- Generate safe unique filenames (e.g., UUID or hash-based)
- Derive extension from validated MIME type, not user input:
```php
$extension = $file->guessExtension(); // Based on MIME
$filename = Str::uuid() . '.' . $extension;
```
- Block dangerous extensions at the web server level
- Store uploads outside webroot or in non-executable locations
- Laravel blocks .php by default; ensure .phar is also blocked (Debian RCE risk)

**Warning Signs:**
- Using `$file->getClientOriginalName()` for storage
- Files stored with user-provided extensions
- Upload directory is web-accessible and executable
- No blocklist for dangerous extensions

**Phase to Address:** Implementation (when building storage logic)

---

### 6. Spaces in File Paths Causing Failures

**What Goes Wrong:**
Your requirement to extract files to paths with user full names (e.g., `/storage/extracts/John Smith/`) will fail on many operations. The `storage_path()` helper doesn't escape spaces, causing "Too many arguments" errors when paths are used in shell commands.

**Why It Happens:**
- Laravel's `storage_path()` doesn't escape special characters
- Many underlying operations shell out to system commands
- Developers test with simple paths, not real user names
- Path handling differences between Windows and Unix

**How to Avoid:**
- Sanitize directory names or use IDs instead of names:
```php
// Use user ID, not name
$extractPath = storage_path("app/extracts/{$user->id}");

// Or sanitize names
$safeName = Str::slug($user->name, '_');
$extractPath = storage_path("app/extracts/{$safeName}");
```
- Always quote paths in any shell operations
- Use Laravel's Storage facade rather than direct filesystem calls
- Test with names containing spaces, apostrophes, and unicode characters

**Warning Signs:**
- Using user input directly in file paths
- No sanitization of directory/file names
- "Too many arguments" or "No such file" errors
- Works for some users but not others

**Phase to Address:** Architecture (design file storage strategy upfront)

---

### 7. Queue Job Timeouts Without Proper Handling

**What Goes Wrong:**
File processing jobs timeout silently, leaving files in inconsistent states. The job is retried (potentially while still running), causing duplicate processing or corrupted results.

**Why It Happens:**
- `pcntl` extension not installed (required for timeouts to work)
- `retry_after` configuration lower than actual job duration
- IO-blocking operations (HTTP, file reads) don't respect PHP timeout
- Jobs killed by OS but Laravel doesn't know, so job runs again
- No logging when jobs fail due to timeout

**How to Avoid:**
```php
class ProcessZipUpload implements ShouldQueue
{
    public $timeout = 600; // 10 minutes
    public $failOnTimeout = true; // Don't retry on timeout
    public $tries = 3;

    // In config/queue.php, ensure retry_after > timeout
    // 'retry_after' => 900, // 15 minutes
}
```
- Install `pcntl` extension
- Set `failOnTimeout = true` to prevent zombie retries
- Log job start/end for debugging
- Monitor `failed_jobs` table actively
- Set timeouts on HTTP clients and streams separately

**Warning Signs:**
- Jobs running longer than expected
- Duplicate processing of the same file
- `failed_jobs` table growing
- No visibility into job execution status

**Phase to Address:** Queue Configuration (before implementing async processing)

---

### 8. Temporary File Accumulation

**What Goes Wrong:**
Temporary files from uploads, extractions, and processing accumulate indefinitely, eventually filling disk space. Server becomes unresponsive or uploads start failing.

**Why It Happens:**
- No cleanup scheduled for temp directories
- Errors interrupt processing before cleanup code runs
- Livewire stores uploads in `livewire-tmp/` without default cleanup
- Developers forget to delete temp files in error paths
- No monitoring of disk usage

**How to Avoid:**
- Schedule regular cleanup:
```php
// In App\Console\Kernel
$schedule->command('clean:directories')->daily();
// Or use packages: spatie/laravel-directory-cleanup
```
- Use try/finally for guaranteed cleanup:
```php
try {
    $tempPath = $this->extractZip($file);
    $this->processFiles($tempPath);
} finally {
    File::deleteDirectory($tempPath);
}
```
- Configure Livewire for automatic cleanup (24h default on S3)
- Monitor disk space with alerts at 70%, 85%, 95%
- Extract to a separate partition with quotas

**Warning Signs:**
- Disk usage growing over time
- `storage/app/temp` or `livewire-tmp` directories large
- Random "disk full" errors
- Older files lingering in temp directories

**Phase to Address:** Operations (set up before production, monitor ongoing)

---

### 9. Excel Import Memory Exhaustion

**What Goes Wrong:**
Processing Excel files with thousands of rows causes memory exhaustion. Laravel-Excel/PHPSpreadsheet loads entire files into memory by default, causing "Allowed memory size exhausted" errors.

**Why It Happens:**
- PHPSpreadsheet loads entire worksheet into memory
- Default configuration doesn't use chunking
- Infinite loop bugs in certain Excel files (Laravel-Excel issue #2769)
- Large files with complex formatting consume more memory

**How to Avoid:**
```php
class ExcelImport implements WithChunkReading, WithBatchInserts
{
    public function chunkSize(): int
    {
        return 1000; // Process 1000 rows at a time
    }

    public function batchSize(): int
    {
        return 500; // Insert 500 at a time
    }
}
```
- Use `WithChunkReading` and `WithBatchInserts` concerns
- Consider CSV for very large files (more efficient)
- Queue Excel processing: `Excel::queueImport()`
- Set memory monitoring and limits on import jobs
- Test with production-sized files, not samples

**Warning Signs:**
- Memory errors on Excel import
- Import works for small files but fails on large ones
- Server becoming unresponsive during imports
- Import process running indefinitely

**Phase to Address:** Implementation (when building Excel processing)

---

### 10. ZIP Unicode Filename Encoding Issues

**What Goes Wrong:**
Filenames with non-ASCII characters (accents, Cyrillic, Japanese, etc.) become garbled or cause extraction failures. ZIP format uses IBM Code Page 437 by default, not UTF-8.

**Why It Happens:**
- ZIP standard predates Unicode
- PHP's ZipArchive has documented bugs with UTF-8 (Bug #65815, #71034, #53948)
- Files created on different OS may use different encodings
- No encoding flag set in archive

**How to Avoid:**
- Normalize filenames during extraction:
```php
$filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $originalName);
// Or use slug-based names
$filename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;
```
- Set locale before operations: `setlocale(LC_ALL, 'en_US.UTF-8');`
- Consider using OS binaries (unzip) instead of ZipArchive for problematic files
- Document encoding requirements for users creating archives
- Test with files containing non-ASCII characters

**Warning Signs:**
- Garbled filenames after extraction
- "File not found" errors when expected files exist
- Works with some ZIP files but not others
- Different behavior on different servers

**Phase to Address:** Implementation (when building extraction logic)

---

## Technical Debt Patterns

| Pattern | Short-term Gain | Long-term Cost | Better Alternative |
|---------|----------------|----------------|-------------------|
| Storing uploads in `public/` directly | Quick access, no symlink needed | Security exposure, difficult migrations | Use `storage/app/private/` with controlled access |
| Using `getClientOriginalName()` | Preserves user's filename | Security vulnerability, encoding issues | Generate UUID-based names, store original in DB |
| Synchronous file processing | Simpler implementation | Timeouts, poor UX, server strain | Queue-based processing with progress tracking |
| No file size limits | "It works" for test files | DoS vulnerability, disk exhaustion | Validate at upload, extraction, and processing |
| Single disk for all storage | Simple configuration | No isolation, cascading failures | Separate disks: temp, uploads, processed |
| Hardcoded paths in code | Fast to implement | Breaks across environments | Use config/env variables, Storage facade |
| Trusting validation rules alone | Quick validation setup | Bypass vulnerabilities | Defense in depth: validate, sanitize, verify |
| No cleanup strategy | Works initially | Disk fills over time | Scheduled cleanup, temp file management |

---

## Performance Traps

| Trap | Impact | Detection | Solution |
|------|--------|-----------|----------|
| Loading entire ZIP into memory | OOM on large files | Memory profiling, error logs | Stream extraction file-by-file |
| Synchronous extraction in request | 30s+ request times, timeouts | Slow response times, HTTP timeouts | Queue job with progress webhook |
| Reading entire Excel for validation | OOM, slow response | Memory errors on large files | Stream first N rows only |
| N+1 queries on file metadata | Slow batch processing | Query logging, Debugbar | Eager load, batch queries |
| No database indexes on file lookups | Slow searches as data grows | Slow queries, explain analyze | Index upload_batch_id, user_id, status |
| Extracting then re-reading files | Double IO, wasted time | Profiling shows duplicate reads | Process during extraction |
| Full table scans for file status | Slow dashboard, reports | Query explain shows seq scan | Add status indexes, denormalize counts |
| Validating files sequentially | Linear time scaling | 9 files = 9x single file time | Parallel validation where possible |

---

## Security Mistakes

| Mistake | Risk Level | Attack Vector | Mitigation |
|---------|------------|---------------|------------|
| Trusting file extension | CRITICAL | Upload shell.php.jpg, execute as PHP | Generate names from MIME, not extension |
| No path traversal check | CRITICAL | Zip slip: ../../etc/passwd | Validate realpath stays in target dir |
| Uploads in webroot | HIGH | Direct access to malicious uploads | Store outside webroot, serve via controller |
| Using user input in paths | HIGH | Path injection: `/files/../../etc/passwd` | Sanitize, use IDs not names |
| No virus scanning | MEDIUM | Malware distribution | ClamAV integration, external scanning |
| Missing rate limits | MEDIUM | DoS via upload flood | Throttle by IP, user, file size |
| No content verification | MEDIUM | Fake MIME type with malicious content | Magic byte verification |
| Exposing internal paths | LOW | Information disclosure | Use Storage::url(), not direct paths |
| No audit logging | LOW | Undetected misuse | Log all uploads with user, IP, file hash |
| Weak temp file permissions | LOW | Local file read | Set restrictive permissions (0600) |

---

## UX Pitfalls

| Pitfall | User Impact | Root Cause | Better Approach |
|---------|-------------|------------|-----------------|
| No upload progress | Users re-upload, duplicate submissions | Synchronous upload, no AJAX progress | Progress bar with real-time updates |
| Generic error messages | Users can't fix issues, support burden | Catching exceptions without context | Specific errors: "File X: wrong format, expected .xlsx" |
| Timeout without feedback | Users think it's broken | No timeout handling on frontend | Show processing status, queue with webhooks |
| All-or-nothing validation | One bad file = restart everything | Single validation pass | Validate each file, report all issues at once |
| No partial save | Crashed session = lost work | Only save on complete success | Save progress, allow resume |
| Missing required file unclear | "Validation failed" without specifics | Generic validation messages | "Missing: transport_manifest.xlsx in folder 'January 2024'" |
| Silent failures | Files appear processed but aren't | Swallowed exceptions | Error states, admin alerts, user notifications |
| No file preview | Upload wrong file, discover late | No pre-upload verification | Preview images, show file metadata |
| Unclear size limits | Uploads fail mysteriously | Limits enforced but not communicated | Show limits clearly, validate before upload |

---

## "Looks Done But Isn't" Checklist

### File Upload Handling
- [ ] Tested with files > 100MB?
- [ ] Tested with 10+ concurrent uploads?
- [ ] Tested with slow/interrupted connections?
- [ ] Handles disk full scenario gracefully?
- [ ] Cleanup runs if upload fails midway?

### ZIP Extraction
- [ ] Path traversal protection implemented and tested?
- [ ] Zip bomb detection implemented?
- [ ] Unicode filenames handled correctly?
- [ ] Handles nested ZIPs appropriately?
- [ ] Max extraction depth/count limits set?

### File Validation
- [ ] Validates MIME type AND extension?
- [ ] Checks magic bytes, not just extension?
- [ ] Array validation with CVE-2025-27515 fix?
- [ ] Handles empty files?
- [ ] Handles zero-byte files disguised as valid?

### Path Handling
- [ ] Tested with spaces in paths?
- [ ] Tested with special characters (apostrophes, unicode)?
- [ ] Works on Windows AND Unix?
- [ ] No hardcoded path separators?
- [ ] symlinks configured for deployment?

### Excel Processing
- [ ] Tested with 10,000+ row files?
- [ ] Handles malformed Excel gracefully?
- [ ] Pattern matching works with different Excel conventions?
- [ ] Validated with real production files?
- [ ] Memory usage acceptable under load?

### Queue Processing
- [ ] pcntl extension installed?
- [ ] retry_after > max job timeout?
- [ ] Failed jobs monitored and alerted?
- [ ] Duplicate processing prevented?
- [ ] Job progress trackable?

### Cleanup & Maintenance
- [ ] Temp file cleanup scheduled?
- [ ] Old uploads cleaned up?
- [ ] Disk space monitoring configured?
- [ ] Failed upload artifacts cleaned?
- [ ] Database indexes on file tables?

### Error Handling
- [ ] User sees specific, actionable errors?
- [ ] Errors logged with context?
- [ ] Partial failures don't lose all progress?
- [ ] Timeout scenarios handled?
- [ ] Corrupt file scenarios handled?

---

## Pitfall-to-Phase Mapping

| Phase | Pitfalls to Address |
|-------|---------------------|
| **Architecture & Planning** | Memory strategy, path naming convention, storage disk layout, queue configuration |
| **Security Review** | Zip slip, MIME bypass, path traversal, CVE patches |
| **Database Design** | Indexes for file queries, status tracking, audit logging |
| **Upload Implementation** | Chunking, progress tracking, size limits, validation bypass protection |
| **Extraction Implementation** | Path traversal check, zip bomb detection, encoding handling |
| **Excel Processing** | Memory management, chunk reading, pattern matching |
| **Queue Implementation** | Timeout configuration, retry_after, failure handling |
| **Error Handling** | Specific messages, partial failure recovery, cleanup |
| **Testing** | Large files, concurrent users, special characters, edge cases |
| **Pre-Production** | Symlinks, permissions, cleanup schedules, monitoring |
| **Operations** | Disk monitoring, failed job alerts, temp cleanup |

---

## Sources

### High Confidence (Official Documentation, Security Advisories)
- [Laravel File Storage Documentation](https://laravel.com/docs/12.x/filesystem)
- [Laravel Queues Documentation](https://laravel.com/docs/12.x/queues)
- [CVE-2025-27515 - Laravel File Validation Bypass](https://github.com/laravel/framework/security/advisories/GHSA-78fx-h6xr-vch4)
- [Laravel Enlightn File Bomb Analyzer](https://www.laravel-enlightn.com/docs/security/file-bomb-validation-analyzer.html)
- [OWASP Laravel Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html)

### Medium-High Confidence (Security Databases, Known CVEs)
- [Snyk: Zip Slip in madnest/madzipper](https://security.snyk.io/vuln/SNYK-PHP-MADNESTMADZIPPER-552164)
- [Snyk: Directory Traversal in laravel-filemanager](https://security.snyk.io/vuln/SNYK-PHP-UNISHARPLARAVELFILEMANAGER-3023725)
- [PHP Bug #65815: ZipArchive UTF-8 Issues](https://bugs.php.net/bug.php?id=65815)
- [Laravel Framework Issue #31159: Memory on Large Downloads](https://github.com/laravel/framework/issues/31159)

### Medium Confidence (Technical Articles, Tutorials)
- [Handling Large File Uploads in Laravel](https://hafiz.dev/blog/handling-large-file-uploads-in-laravel-without-crashing-your-server)
- [Laravel Excel Row Validation](https://docs.laravel-excel.com/3.1/imports/validation.html)
- [Laravel Excel Chunk Reading](https://docs.laravel-excel.com/3.1/imports/concerns.html)
- [How Laravel Fails and Retries Queued Jobs](https://sjorso.com/how-laravel-fails-and-retries-queued-jobs)
- [Managing Temporary Files in Laravel](https://accreditly.io/articles/managing-temporary-files-in-laravel)
- [Laravel File Upload Security](https://securinglaravel.com/laravel-security-file-upload-vulnerability/)

### Medium-Low Confidence (Community Discussions, Forum Posts)
- [Laracasts: File Upload Limit Issues](https://laracasts.com/discuss/channels/laravel/file-upload-limit-issues)
- [Laravel Framework Issue #11320: storage_path spaces](https://github.com/laravel/framework/issues/11320)
- [Laravel-Excel Memory Issues](https://github.com/Maatwebsite/Laravel-Excel/issues/2166)
- [Livewire File Upload Production Issues](https://medium.com/@christogonusobasi/laravel-livewire-shows-error-file-failed-to-upload-after-selecting-a-file-on-production-server-34827a073ce7)

### Lower Confidence (General Best Practices, Blog Posts)
- [File Uploader UX Best Practices](https://uploadcare.com/blog/file-uploader-ux-best-practices/)
- [Preventing Race Conditions in Laravel](https://www.twilio.com/en-us/blog/developers/tutorials/prevent-race-conditions-laravel-atomic-locks)
- [Bypassing MIME Type Filters](https://medium.com/@abdelaazizbenafghoul/bypassing-extension-and-mime-type-filters-in-file-upload-attacks-d099dc7cb4c6)

---

## Domain-Specific Recommendations

Given your specific context (government waste transport records, zip uploads, 9 files per folder, Excel filename patterns, user name paths), pay special attention to:

1. **Government Compliance**: Audit logging is likely mandatory. Log every file operation with user, timestamp, IP, and file hash.

2. **9 Files Per Folder Validation**: Use explicit array validation with exact count requirements, not wildcards alone. Validate each expected file exists with correct naming pattern.

3. **User Full Names in Paths**: Strongly recommend using user IDs or sanitized slugs instead. If full names required, test thoroughly with names like "O'Brien", "Maria Jose", "Jean-Pierre", and international names.

4. **Excel Filename Pattern Matching**: Use `File::glob()` with explicit patterns, then validate results. Handle case sensitivity differences across OS. Document expected naming conventions clearly.

5. **Multiple Folders in ZIP**: Validate folder structure before processing. Each folder should be validated independently. Implement transaction-like behavior: either all folders process or none.
