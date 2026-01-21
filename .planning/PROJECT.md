# RecycleUI

## What This Is

A Laravel web platform for collecting and validating waste transport documentation for the SIATD government system. Users upload zip archives containing transport records (photos, PDFs, Excel files), which are validated against a strict file schema before extraction to organized folders for downstream processing.

## Core Value

Ensure every uploaded transport document set is complete and correctly structured before it enters the processing pipeline. Reject incomplete submissions with clear feedback.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] User authentication with username/password (admin-created accounts only, no self-registration)
- [ ] Zip file upload interface
- [ ] Validation engine that checks each folder in zip for required files:
  - ANEXA.pdf
  - AVIZ.pdf
  - Fata.jpeg
  - Inc1.jpeg
  - Inc2.jpeg
  - Km.jpeg
  - Lateral.jpeg
  - Spate.jpeg
  - Excel file matching pattern `Iesiri_export_robotel_siatd_intern_*_*_*_*_*_*.xlsx`
- [ ] Detailed validation error messages showing exactly which folder is missing which file
- [ ] Extract valid zips to `~/Desktop/SIATD/Processing/[User Full Name]/[DD-MM-YYYY HH:mm]/`
- [ ] History view listing user's processed uploads from `~/Desktop/SIATD/Done/[User Full Name]/[datetime]/`
- [ ] Admin interface to create/manage user accounts

### Out of Scope

- Self-registration — admin creates all accounts
- Moving files from Processing to Done — handled by external process
- Excel content validation — only filename pattern is checked
- Multi-language support — Romanian only for now

## Context

**Domain:** SIATD is an existing government platform for processed trash/waste transport documents. This platform acts as an intake system for transport records before they're processed by external systems.

**File schema rationale:**
- Inc1.jpeg, Inc2.jpeg — photos of truck contents
- Fata.jpeg, Spate.jpeg, Lateral.jpeg — truck exterior photos (front, back, side)
- Km.jpeg — odometer/kilometer reading photo
- ANEXA.pdf, AVIZ.pdf — official transport documents
- Excel file — export from robotel system with timestamp in filename

**Folder structure:** Each zip contains multiple folders (one per transport record). Each folder must contain the complete file set. If any folder is incomplete, the entire upload is rejected.

**External integration:** Files land in Processing/, an external process picks them up and moves completed records to Done/. This platform only reads from Done/ for history display.

## Constraints

- **Tech stack**: Laravel + MySQL — required
- **Deployment**: Local desktop environment (~/Desktop/SIATD/)
- **File paths**: Must handle spaces in user full names
- **Datetime format**: DD-MM-YYYY HH:mm (using dashes for filesystem compatibility)

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Admin-only account creation | Government system, controlled access | — Pending |
| Reject entire zip if any folder invalid | Ensures complete document sets | — Pending |
| Detailed error messages | Users need to know exactly what's missing to fix | — Pending |
| External process handles Processing→Done | Separation of concerns, existing workflow | — Pending |

---
*Last updated: 2026-01-21 after initialization*
