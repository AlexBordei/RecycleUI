# Requirements: RecycleUI

**Defined:** 2026-01-21
**Core Value:** Ensure every uploaded transport document set is complete and correctly structured before it enters the processing pipeline. Reject incomplete submissions with clear feedback.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Authentication

- [ ] **AUTH-01**: User can login with username and password
- [ ] **AUTH-02**: User can logout and end session
- [ ] **AUTH-03**: User session persists across browser refresh
- [ ] **AUTH-04**: User can reset password via email link (Resend API)

### File Upload

- [ ] **UPLD-01**: User can drag-and-drop zip file onto upload zone
- [ ] **UPLD-02**: User can click button to open file picker as fallback
- [ ] **UPLD-03**: User sees progress bar during upload
- [ ] **UPLD-04**: System rejects non-zip files with clear message
- [ ] **UPLD-05**: System rejects oversized files with clear limit shown
- [ ] **UPLD-06**: User can cancel in-progress upload
- [ ] **UPLD-07**: User can remove/replace file before submission
- [ ] **UPLD-08**: User can preview zip contents (file listing) before submission

### Validation Engine

- [ ] **VALD-01**: System validates each folder contains 9 required files (ANEXA.pdf, AVIZ.pdf, Fata.jpeg, Inc1.jpeg, Inc2.jpeg, Km.jpeg, Lateral.jpeg, Spate.jpeg, Excel)
- [ ] **VALD-02**: System validates Excel filename matches pattern `Iesiri_export_robotel_siatd_intern_*`
- [ ] **VALD-03**: System shows detailed error messages identifying exactly which folder is missing which file
- [ ] **VALD-04**: System rejects entire zip if any folder is invalid

### File Extraction

- [ ] **EXTR-01**: System extracts valid zips to `~/Desktop/SIATD/Processing/[User Full Name]/[DD-MM-YYYY HH:mm]/`
- [ ] **EXTR-02**: System handles spaces and special characters in user full names (e.g., "ALEXANDRU-IONEL BORDEI")
- [ ] **EXTR-03**: System processes large files via background queue jobs
- [ ] **EXTR-04**: User receives email notification when processing completes (via Resend)

### History & Audit

- [ ] **HIST-01**: User can browse their files from `~/Desktop/SIATD/Done/[User Full Name]/[datetime]/`
- [ ] **HIST-02**: User can view audit trail showing their upload actions with timestamps

### Admin Panel

- [ ] **ADMN-01**: Admin can create user accounts with username, password, and full name
- [ ] **ADMN-02**: Admin can edit user details (full name, password)
- [ ] **ADMN-03**: Admin can deactivate or delete user accounts
- [ ] **ADMN-04**: Admin can view list of all users
- [ ] **ADMN-05**: Admin can view upload monitoring dashboard (all uploads system-wide)

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### History Enhanced

- **HIST-03**: Upload history table with pagination
- **HIST-04**: Status filtering (valid/invalid/pending)
- **HIST-05**: Date range filtering

### Validation Enhanced

- **VALD-05**: Downloadable error reports (CSV/PDF export)

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Self-registration | Admin creates all accounts (government control) |
| Processing → Done movement | External process handles this |
| Excel content validation | Only filename pattern checked |
| Multi-language support | Romanian only for v1 |
| Real-time WebSocket updates | Polling sufficient for v1 |
| Resumable uploads | Complexity, defer to v2+ |
| Batch upload | Single zip at a time for v1 |
| Smart error suggestions | AI/rule-based, too complex for v1 |

## Traceability

Which phases cover which requirements. Updated by create-roadmap.

| Requirement | Phase | Status |
|-------------|-------|--------|
| AUTH-01 | 1 - Foundation | Pending |
| AUTH-02 | 1 - Foundation | Pending |
| AUTH-03 | 1 - Foundation | Pending |
| AUTH-04 | 1 - Foundation | Pending |
| UPLD-01 | 2 - File Upload Core | Pending |
| UPLD-02 | 2 - File Upload Core | Pending |
| UPLD-03 | 2 - File Upload Core | Pending |
| UPLD-04 | 2 - File Upload Core | Pending |
| UPLD-05 | 2 - File Upload Core | Pending |
| UPLD-06 | 2 - File Upload Core | Pending |
| UPLD-07 | 2 - File Upload Core | Pending |
| UPLD-08 | 3 - Zip Validation Engine | Pending |
| VALD-01 | 3 - Zip Validation Engine | Pending |
| VALD-02 | 3 - Zip Validation Engine | Pending |
| VALD-03 | 3 - Zip Validation Engine | Pending |
| VALD-04 | 3 - Zip Validation Engine | Pending |
| EXTR-01 | 4 - File Extraction | Pending |
| EXTR-02 | 4 - File Extraction | Pending |
| EXTR-03 | 7 - Queue Processing | Pending |
| EXTR-04 | 7 - Queue Processing | Pending |
| HIST-01 | 5 - History & Dashboard | Pending |
| HIST-02 | 5 - History & Dashboard | Pending |
| ADMN-01 | 6 - Admin Panel | Pending |
| ADMN-02 | 6 - Admin Panel | Pending |
| ADMN-03 | 6 - Admin Panel | Pending |
| ADMN-04 | 6 - Admin Panel | Pending |
| ADMN-05 | 6 - Admin Panel | Pending |

**Coverage:**
- v1 requirements: 27 total
- Mapped to phases: 27 (100%)
- Unmapped: 0

---
*Requirements defined: 2026-01-21*
*Last updated: 2026-01-21 after initial definition*
