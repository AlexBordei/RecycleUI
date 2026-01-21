# Project Research Summary

**Project:** RecycleUI
**Domain:** Government document intake/validation system (waste transport records)
**Researched:** 2026-01-21
**Confidence:** HIGH

## Executive Summary

RecycleUI is a Laravel document intake system requiring robust zip file handling, strict validation (9 files per folder), and filesystem operations to external paths. Research confirms Laravel's ecosystem is well-suited with **Filament v3** for admin, **native ZipArchive** with validation wrappers for security, and **queue-based processing** for reliability.

The recommended approach prioritizes **security-first file handling** (Zip Slip prevention, path traversal checks), **layered validation** (Form Request → Custom Rule → Pipeline), and **Service+Action architecture** for maintainable business logic. Critical pitfall: user full names in paths require sanitization or ID-based alternatives.

Key risks center on file security (multiple known CVEs in zip packages) and path handling (spaces in user names). Mitigation strategies are well-documented with high confidence.

## Key Findings

### Recommended Stack

Laravel + MySQL with these core packages:

**Core technologies:**
- **Filament v3** — Admin panel, user management, CRUD. Free, Livewire-based, excellent plugin ecosystem.
- **PHP ZipArchive + orkhanahmadov/laravel-zip-validator** — Secure zip handling with pre-extraction validation.
- **spatie/laravel-medialibrary v11** — File associations, thumbnails, storage abstraction.
- **maatwebsite/excel v3.1** — Excel filename validation (142M downloads, well-maintained).
- **spatie/livewire-filepond** — Modern drag-drop upload UI with progress.

### Expected Features

**Must have (table stakes):**
- Drag-and-drop upload with click fallback
- Progress indicator during upload
- Clear success/error messages with specific details
- Upload history with pagination and status filtering
- Basic validation feedback

**Should have (v1.x):**
- Zip contents preview before submission
- Inline validation errors (per-folder/file)
- Email notifications on completion
- Downloadable error reports (CSV)

**Defer (v2+):**
- WebSocket real-time updates
- Resumable uploads
- Batch processing

### Architecture Approach

**Service + Action + Pipeline pattern** for clean separation:

1. **Controller** — Thin, HTTP handling only
2. **Form Request** — Basic validation (size, type)
3. **Custom Rule** — ZIP structure validation before extraction
4. **Service** — Orchestrates actions and jobs
5. **Actions** — Single-responsibility (Extract, Validate, Move)
6. **Pipeline** — Sequential validation chain (9 files per folder)
7. **Queue Jobs** — Background processing for large files

**Major components:**
1. **DocumentProcessingService** — Main orchestrator
2. **ZipValidationPipeline** — 5-stage validation chain
3. **Custom Filesystem Disks** — `processing` and `done` paths
4. **Upload History Repository** — Status tracking and history

### Critical Pitfalls

1. **Zip Slip (Path Traversal)** — Validate all extracted paths stay within target directory. Multiple Laravel zip packages have had this CVE.

2. **Spaces in User Full Names** — Requirement to use `[User Full Name]/` in paths will cause issues. Strongly recommend using user IDs or sanitized slugs: `Str::slug($user->full_name, '_')`.

3. **Memory Exhaustion** — Large zips with 9 files × many folders. Use streaming extraction and queue processing for files >5MB.

4. **CVE-2025-27515** — Recent Laravel validation bypass. Ensure Laravel 11.44.1+ or 12.1.1+ and validate both array and individual elements.

5. **Zip Bomb** — Check decompressed size before extraction. Set max 100MB decompressed limit.

## Implications for Roadmap

Based on research, suggested phase structure:

### Phase 1: Foundation
**Rationale:** Security and architecture must be correct from the start
**Delivers:** Laravel project, database, authentication, custom filesystem disks
**Addresses:** Admin-created accounts, secure file storage configuration
**Avoids:** Hardcoded paths pitfall, security foundation issues

### Phase 2: File Upload Core
**Rationale:** Upload UI must work before validation can be tested
**Delivers:** Drag-drop upload, progress indicator, basic validation
**Uses:** Filament/Livewire-filepond, Form Request validation
**Avoids:** Memory exhaustion (chunked uploads), UX pitfalls

### Phase 3: Zip Validation Engine
**Rationale:** Core value proposition - detailed validation
**Delivers:** Extract, validate 9-file schema, detailed error messages
**Implements:** ZipValidationPipeline, custom validation rule
**Avoids:** Zip Slip, Zip Bomb, path traversal

### Phase 4: File Extraction & Storage
**Rationale:** Depends on validation engine
**Delivers:** Extract to Processing folder, user-organized directories
**Addresses:** Path handling with spaces, filesystem operations
**Avoids:** Path encoding issues, cleanup failures

### Phase 5: History & Dashboard
**Rationale:** Users need to see past uploads
**Delivers:** History table, status filtering, file browsing from Done folder
**Uses:** Filament tables, pagination
**Addresses:** User visibility requirement

### Phase 6: Admin Panel
**Rationale:** Admin functionality after user features
**Delivers:** User CRUD, upload monitoring, system status
**Uses:** Filament admin panel
**Avoids:** Scope creep into user features

### Phase 7: Queue Processing
**Rationale:** Performance optimization for large files
**Delivers:** Background processing, progress tracking, email notifications
**Addresses:** Large file handling, timeout prevention
**Avoids:** Queue timeout pitfalls, zombie retries

### Phase 8: Polish & Security Hardening
**Rationale:** Final verification before production
**Delivers:** Error handling, audit logging, security review
**Addresses:** Government compliance (audit trails)
**Avoids:** "Looks done but isn't" issues

### Phase Ordering Rationale

- **Foundation first** — Security and architecture decisions compound
- **Upload before validation** — Can't test validation without upload working
- **Validation before extraction** — Don't extract invalid files
- **History after extraction** — Needs real data to display
- **Queue last** — Optimization after core works synchronously

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 3 (Validation):** Excel filename pattern regex complexity
- **Phase 4 (Extraction):** Unicode filename handling edge cases
- **Phase 7 (Queue):** Horizon configuration for this workload

Phases with standard patterns (skip research-phase):
- **Phase 1 (Foundation):** Standard Laravel setup
- **Phase 5 (History):** Standard Filament tables
- **Phase 6 (Admin):** Standard Filament CRUD

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Filament, Spatie packages well-documented, 11M+ downloads |
| Features | HIGH | Standard patterns, Nielsen Norman UX research backing |
| Architecture | HIGH | Laravel-documented patterns, community consensus |
| Pitfalls | HIGH | Multiple CVEs documented, post-mortems available |

**Overall confidence:** HIGH

### Gaps to Address

- **Excel filename pattern:** Exact regex pattern `Iesiri_export_robotel_siatd_intern_*` needs validation with real files
- **Path sanitization strategy:** Decide between user ID vs. sanitized slug approach
- **File case sensitivity:** JPEG vs jpeg in validation across OS
- **External process integration:** How Processing→Done movement triggers history refresh

## Sources

### Primary (HIGH confidence)
- Laravel 12.x Documentation — File storage, queues, validation
- Filament PHP v3 Documentation — Admin panel, forms
- Spatie Laravel Media Library v11 — File management
- CVE-2025-27515 Advisory — Validation bypass

### Secondary (MEDIUM confidence)
- Laravel News — Package recommendations
- Nielsen Norman Group — UX validation patterns
- Community tutorials — Service/Action patterns

### Tertiary (LOW confidence)
- Forum discussions — Edge case handling
- Blog posts — Specific implementation tips

---
*Research completed: 2026-01-21*
*Ready for roadmap: yes*
