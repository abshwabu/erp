<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StorageLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:link
                {--relative : Create the symbolic link using relative paths}
                {--force : Recreate existing symbolic links}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the symbolic links configured for the application (relative for Docker/host portability)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $publicStorage = public_path('storage');
        $relativeTarget = '../storage/app/public';
        $storageDir = storage_path('app/public');

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        if (file_exists($publicStorage) || is_link($publicStorage)) {
            if (!$this->option('force')) {
                $currentTarget = is_link($publicStorage) ? readlink($publicStorage) : null;
                if ($currentTarget === $relativeTarget) {
                    $this->components->info('The [public/storage] link already exists and is relatively linked.');
                    return self::SUCCESS;
                }
            }

            if (is_link($publicStorage) || is_file($publicStorage)) {
                unlink($publicStorage);
            } elseif (is_dir($publicStorage)) {
                $this->components->error('The [public/storage] path exists and is a directory.');
                return self::FAILURE;
            }
        }

        $cwd = getcwd();
        chdir(public_path());
        symlink($relativeTarget, 'storage');
        if ($cwd) {
            chdir($cwd);
        }

        $this->components->info('The [public/storage] link has been connected to [../storage/app/public].');
        return self::SUCCESS;
    }
}
