# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

RecycleUI is a Laravel + MySQL platform for authenticated file archive management. Users upload .zip archives containing structured document sets that are validated, extracted, and organized by user and timestamp.

## Core Workflow

1. **Authentication**: Users log in with username/password
2. **Upload**: User uploads a .zip archive
3. **Validation**: System extracts and validates folder contents against required schema
4. **Storage**: Valid archives are extracted to `~/Desktop/SIATD/Processing/[User Full Name]/[dd-mm-yyyy HH:mm]/`
5. **History**: Users view their processing history from `~/Desktop/SIATD/Done/[User Full Name]/[datetime]/`

## File Validation Schema

Each folder in the uploaded .zip must contain:

**Images (6):**
- `Inc1.jpeg` - Truck contents photo 1
- `Inc2.jpeg` - Truck contents photo 2
- `Fata.jpeg` - Truck front photo
- `Spate.jpeg` - Truck back photo
- `Lateral.jpeg` - Truck side photo
- `Km.jpeg` - Odometer/kilometer reading photo

**PDFs (2):**
- `ANEXA.pdf` - Official transport document
- `AVIZ.pdf` - Transport notice document

**Excel (1):**
- File matching pattern `Iesiri_export_robotel_siatd_intern_DD_MM_YYYY_HH_MM_SS.xlsx`

Upload is rejected with detailed errors if any folder is missing required files.

## Build/Development Commands

```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Development server
php artisan serve

# Frontend assets (if using Vite)
npm run dev

# Production build
npm run build
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ZipUploadTest.php

# Run specific test method
php artisan test --filter=test_zip_validation_rejects_incomplete_folders

# Run with coverage
php artisan test --coverage
```

## Architecture

### Directory Structure (Application)
- `app/Http/Controllers/UploadController.php` - Handles zip upload, validation, extraction
- `app/Http/Controllers/HistoryController.php` - Lists user's processed files by date
- `app/Services/ZipValidatorService.php` - Validates zip contents against required schema
- `app/Services/FileExtractorService.php` - Handles extraction to user directories

### External Directory Structure
```
~/Desktop/SIATD/
├── Processing/           # Active uploads being processed
│   └── [User Full Name]/
│       └── [dd-mm-yyyy HH:mm]/
│           └── [extracted folders]/
└── Done/                 # Completed processing history
    └── [User Full Name]/
        └── [datetime]/
            └── [processed folders]/
```

### Key Configuration
- `.env`: Set `SIATD_BASE_PATH` for the SIATD directory location
- Database: MySQL connection configured in `.env`
- User model includes `full_name` field for directory naming

## Database Schema

### Users Table
- `id`, `username`, `password`, `full_name`, `email`, timestamps

### Uploads Table (optional tracking)
- `id`, `user_id`, `original_filename`, `status`, `processed_at`, timestamps

## API Endpoints

- `POST /login` - Authenticate user
- `POST /logout` - End session
- `POST /upload` - Upload and validate zip archive
- `GET /history` - List user's processed uploads by date
- `GET /history/{date}` - List files for specific date

## Validation Rules

The `ZipValidatorService` must:
1. Extract zip to temporary location
2. Iterate each top-level folder
3. Check for exact filenames (case-sensitive): `Inc1.jpeg`, `Inc2.jpeg`, `Fata.jpeg`, `ANEXA.PDF`
4. Return validation errors with specific folder/file details
5. Clean up temporary files on failure

## Important Notes

- User directories use `full_name` (not username) with spaces preserved
- Datetime format in paths: `dd-mm-yyyy HH:mm` (use dashes not slashes for filesystem compatibility)
- All file operations should use proper path escaping for names with spaces
- Implement proper cleanup of temporary extraction directories
