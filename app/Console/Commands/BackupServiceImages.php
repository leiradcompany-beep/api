<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupServiceImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup service images to a timestamped folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sourceDir = base_path('../frontend/assets/services');
        $backupBaseDir = storage_path('app/backups/images');
        $timestamp = date('Y-m-d_H-i-s');
        $targetDir = $backupBaseDir . '/' . $timestamp;

        if (!is_dir($sourceDir)) {
            $this->error("Source directory does not exist: {$sourceDir}");
            return;
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $this->info("Starting backup of service images...");
        $this->info("Source: {$sourceDir}");
        $this->info("Target: {$targetDir}");

        $files = scandir($sourceDir);
        $count = 0;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $sourceFile = $sourceDir . '/' . $file;
            $targetFile = $targetDir . '/' . $file;

            if (copy($sourceFile, $targetFile)) {
                $count++;
            } else {
                $this->error("Failed to copy: {$file}");
            }
        }

        $this->info("Backup completed. {$count} files copied.");
    }
}
