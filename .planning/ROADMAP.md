# Roadmap: RecycleUI

**Milestone:** v1.0 - Document Intake System
**Created:** 2026-01-21
**Status:** Planning

## Overview

8 phases delivering a secure document intake system for SIATD waste transport records. Phases ordered by dependencies: foundation → upload → validation → extraction → history → admin → async processing → hardening.

---

## Phase 1: Foundation

**Goal:** Establish secure Laravel project with authentication and filesystem configuration.

**Delivers:**
- Laravel 11+ project with MySQL
- Filament v3 admin panel base
- User authentication (login, logout, session)
- Password reset via Resend API
- Custom filesystem disks for Processing and Done paths
- Security foundation (CSRF, sanitization)

**Requirements:**
- AUTH-01: User can login with username and password
- AUTH-02: User can logout and end session
- AUTH-03: User session persists across browser refresh
- AUTH-04: User can reset password via email link (Resend API)

**Research needed:** No (standard Laravel setup)

---

## Phase 2: File Upload Core

**Goal:** Drag-drop upload interface with progress and basic validation.

**Delivers:**
- Drag-and-drop upload zone
- Click-to-upload fallback
- Progress bar during upload
- File type validation (zip only)
- File size validation with clear limits
- Cancel upload capability
- Remove/replace file before submission

**Requirements:**
- UPLD-01: User can drag-and-drop zip file onto upload zone
- UPLD-02: User can click button to open file picker as fallback
- UPLD-03: User sees progress bar during upload
- UPLD-04: System rejects non-zip files with clear message
- UPLD-05: System rejects oversized files with clear limit shown
- UPLD-06: User can cancel in-progress upload
- UPLD-07: User can remove/replace file before submission

**Research needed:** No (Livewire Filepond patterns documented)

---

## Phase 3: Zip Validation Engine

**Goal:** Validate zip structure with 9-file schema per folder and detailed error reporting.

**Delivers:**
- Zip extraction with security checks (Zip Slip prevention)
- 9-file schema validation per folder
- Excel filename pattern validation
- Detailed error messages per folder/file
- Zip contents preview before submission
- Reject invalid zips with comprehensive feedback

**Requirements:**
- VALD-01: System validates each folder contains 9 required files
- VALD-02: System validates Excel filename matches pattern
- VALD-03: System shows detailed error messages identifying exactly which folder is missing which file
- VALD-04: System rejects entire zip if any folder is invalid
- UPLD-08: User can preview zip contents (file listing) before submission

**Research needed:** Yes (Excel regex pattern, case sensitivity across OS)

---

## Phase 4: File Extraction & Storage

**Goal:** Extract validated zips to user-organized Processing folder.

**Delivers:**
- Extract to `~/Desktop/SIATD/Processing/[User Full Name]/[DD-MM-YYYY HH:mm]/`
- User full name path handling (spaces, special characters)
- Cleanup on extraction failure
- Extraction status tracking

**Requirements:**
- EXTR-01: System extracts valid zips to Processing path
- EXTR-02: System handles spaces and special characters in user full names

**Research needed:** Yes (Unicode filename edge cases)

---

## Phase 5: History & Dashboard

**Goal:** Users can browse their processed files and view audit trail.

**Delivers:**
- File browser for Done folder contents
- Audit trail showing upload actions with timestamps
- User-scoped history (users see only their files)

**Requirements:**
- HIST-01: User can browse their files from Done folder
- HIST-02: User can view audit trail showing their upload actions with timestamps

**Research needed:** No (standard Filament tables)

---

## Phase 6: Admin Panel

**Goal:** Admin user management and system monitoring.

**Delivers:**
- User CRUD (create, edit, deactivate, delete)
- User list with filtering
- Upload monitoring dashboard (all uploads system-wide)
- Admin-only access controls

**Requirements:**
- ADMN-01: Admin can create user accounts with username, password, and full name
- ADMN-02: Admin can edit user details (full name, password)
- ADMN-03: Admin can deactivate or delete user accounts
- ADMN-04: Admin can view list of all users
- ADMN-05: Admin can view upload monitoring dashboard

**Research needed:** No (standard Filament CRUD)

---

## Phase 7: Queue Processing & Notifications

**Goal:** Background processing for large files with email notifications.

**Delivers:**
- Laravel queue configuration
- Background job for large file processing
- Email notification on processing completion
- Progress tracking for queued jobs

**Requirements:**
- EXTR-03: System processes large files via background queue jobs
- EXTR-04: User receives email notification when processing completes (via Resend)

**Research needed:** Yes (Horizon configuration for this workload)

---

## Phase 8: Polish & Security Hardening

**Goal:** Final verification, error handling, and security audit.

**Delivers:**
- Comprehensive error handling
- Security review (OWASP top 10)
- Audit logging verification
- Edge case testing
- Performance optimization
- Documentation

**Requirements:** None (verification phase)

**Research needed:** No (standard security checklist)

---

## Requirement Coverage

| Requirement | Phase | Description |
|-------------|-------|-------------|
| AUTH-01 | 1 | Login with username/password |
| AUTH-02 | 1 | Logout and end session |
| AUTH-03 | 1 | Session persistence |
| AUTH-04 | 1 | Password reset via Resend |
| UPLD-01 | 2 | Drag-and-drop upload |
| UPLD-02 | 2 | Click-to-upload fallback |
| UPLD-03 | 2 | Progress bar |
| UPLD-04 | 2 | Reject non-zip files |
| UPLD-05 | 2 | Reject oversized files |
| UPLD-06 | 2 | Cancel upload |
| UPLD-07 | 2 | Remove/replace file |
| UPLD-08 | 3 | Preview zip contents |
| VALD-01 | 3 | 9-file schema validation |
| VALD-02 | 3 | Excel filename pattern |
| VALD-03 | 3 | Detailed error messages |
| VALD-04 | 3 | Reject invalid zip |
| EXTR-01 | 4 | Extract to Processing path |
| EXTR-02 | 4 | Handle special characters |
| EXTR-03 | 7 | Background queue jobs |
| EXTR-04 | 7 | Email notification |
| HIST-01 | 5 | Browse Done folder |
| HIST-02 | 5 | Audit trail view |
| ADMN-01 | 6 | Create users |
| ADMN-02 | 6 | Edit users |
| ADMN-03 | 6 | Deactivate/delete users |
| ADMN-04 | 6 | View user list |
| ADMN-05 | 6 | Upload monitoring dashboard |

**Coverage:** 27/27 requirements mapped (100%)

---

## Phase Dependencies

```
Phase 1 (Foundation)
    ↓
Phase 2 (Upload)
    ↓
Phase 3 (Validation) ←── requires upload working
    ↓
Phase 4 (Extraction) ←── requires validation engine
    ↓
Phase 5 (History) ←── requires extraction data
    ↓
Phase 6 (Admin) ←── can parallelize with Phase 5
    ↓
Phase 7 (Queue) ←── optimization layer
    ↓
Phase 8 (Polish) ←── final verification
```

---

*Roadmap created: 2026-01-21*
*Last updated: 2026-01-21*
