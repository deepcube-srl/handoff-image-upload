<?php

namespace Deepcube\HandoffImageUpload\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupTempImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'handoff-image:cleanup-temp
                            {--hours=24 : Number of hours after which files should be deleted}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up temporary images older than specified hours from handoff-images/tmp directory';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');
        $disk = Storage::disk('public');
        $tmpDirectory = 'handoff-images/tmp';

        $this->info("Starting cleanup of temporary images older than {$hours} hours...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be actually deleted');
        }

        try {
            // Check if tmp directory exists
            if (! $disk->exists($tmpDirectory)) {
                $this->info("Directory {$tmpDirectory} does not exist. Nothing to clean up.");

                return self::SUCCESS;
            }

            // Get all files in the tmp directory
            $files = $disk->files($tmpDirectory);

            if (empty($files)) {
                $this->info("No files found in {$tmpDirectory}");

                return self::SUCCESS;
            }

            $this->info('Found ' . count($files) . " files in {$tmpDirectory}");

            $deletedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;
            $cutoffTime = now()->subHours($hours)->timestamp;

            foreach ($files as $file) {
                try {
                    $lastModified = $disk->lastModified($file);

                    if ($lastModified < $cutoffTime) {
                        if ($dryRun) {
                            $this->line("Would delete: {$file} (modified: " . date('Y-m-d H:i:s', $lastModified) . ')');
                            $deletedCount++;
                        } else {
                            if ($disk->delete($file)) {
                                $this->line("Deleted: {$file}");
                                Log::info("CleanupTempImagesCommand: Successfully deleted expired temp file: {$file}");
                                $deletedCount++;
                            } else {
                                $this->error("Failed to delete: {$file}");
                                Log::error("CleanupTempImagesCommand: Failed to delete expired temp file: {$file}");
                                $errorCount++;
                            }
                        }
                    } else {
                        $this->line("Skipped (too recent): {$file}");
                        $skippedCount++;
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing file {$file}: " . $e->getMessage());
                    Log::error("CleanupTempImagesCommand: Error processing file {$file}: " . $e->getMessage());
                    $errorCount++;
                }
            }

            // Summary
            $this->newLine();
            $this->info('Cleanup Summary:');
            $this->line('- Files processed: ' . count($files));

            if ($dryRun) {
                $this->line("- Files that would be deleted: {$deletedCount}");
            } else {
                $this->line("- Files deleted: {$deletedCount}");
            }

            $this->line("- Files skipped (too recent): {$skippedCount}");

            if ($errorCount > 0) {
                $this->line("- Errors encountered: {$errorCount}");
                $this->warn('Some errors occurred during cleanup. Check the logs for details.');
            }

            if (! $dryRun) {
                Log::info("CleanupTempImagesCommand completed: {$deletedCount} files deleted, {$skippedCount} skipped, {$errorCount} errors");
            }

            $this->info('Cleanup completed successfully!');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Fatal error during cleanup: ' . $e->getMessage());
            Log::error('CleanupTempImagesCommand fatal error: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
