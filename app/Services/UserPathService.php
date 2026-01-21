<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class UserPathService
{
    /**
     * Generate a user path for file storage.
     *
     * Format: [User Full Name]/[DD-MM-YYYY HH:mm]/
     *
     * @param User $user The user for whom to generate the path
     * @param Carbon|null $timestamp The timestamp for the path (defaults to now)
     * @return string The generated path
     */
    public function generateUserPath(User $user, ?Carbon $timestamp = null): string
    {
        $timestamp = $timestamp ?? Carbon::now();

        // Format: User Full Name/DD-MM-YYYY HH:mm
        // Spaces in full names are preserved (works fine on macOS/Linux)
        $datePart = $timestamp->format('d-m-Y H:i');

        return sprintf('%s/%s', $user->full_name, $datePart);
    }

    /**
     * Get the full processing path for a user.
     *
     * @param User $user The user
     * @param Carbon|null $timestamp The timestamp (defaults to now)
     * @return string The full path in the processing disk
     */
    public function getProcessingPath(User $user, ?Carbon $timestamp = null): string
    {
        $relativePath = $this->generateUserPath($user, $timestamp);

        return Storage::disk('processing')->path($relativePath);
    }

    /**
     * Get the full done path for a user.
     *
     * @param User $user The user
     * @param Carbon|null $timestamp The timestamp (defaults to now)
     * @return string The full path in the done disk
     */
    public function getDonePath(User $user, ?Carbon $timestamp = null): string
    {
        $relativePath = $this->generateUserPath($user, $timestamp);

        return Storage::disk('done')->path($relativePath);
    }

    /**
     * Ensure the user's processing directory exists.
     *
     * @param User $user The user
     * @param Carbon|null $timestamp The timestamp
     * @return string The created path
     */
    public function ensureProcessingDirectoryExists(User $user, ?Carbon $timestamp = null): string
    {
        $relativePath = $this->generateUserPath($user, $timestamp);

        Storage::disk('processing')->makeDirectory($relativePath);

        return Storage::disk('processing')->path($relativePath);
    }

    /**
     * Ensure the user's done directory exists.
     *
     * @param User $user The user
     * @param Carbon|null $timestamp The timestamp
     * @return string The created path
     */
    public function ensureDoneDirectoryExists(User $user, ?Carbon $timestamp = null): string
    {
        $relativePath = $this->generateUserPath($user, $timestamp);

        Storage::disk('done')->makeDirectory($relativePath);

        return Storage::disk('done')->path($relativePath);
    }

    /**
     * List all processing directories for a user.
     *
     * @param User $user The user
     * @return array<string> List of directory names (date-time format)
     */
    public function listUserProcessingDirectories(User $user): array
    {
        return Storage::disk('processing')->directories($user->full_name);
    }

    /**
     * List all done directories for a user (their processing history).
     *
     * @param User $user The user
     * @return array<string> List of directory names (date-time format)
     */
    public function listUserDoneDirectories(User $user): array
    {
        return Storage::disk('done')->directories($user->full_name);
    }
}
