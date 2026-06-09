<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ($this->modulePaths() as $modulePath) {
            $this->loadModuleRoutes($modulePath);
            $this->loadMigrationsFrom($modulePath . '/database/migrations');
        }
    }

    /**
     * @return array<int, string>
     */
    private function modulePaths(): array
    {
        return array_values(array_filter(glob(app_path('Modules/*'), GLOB_ONLYDIR) ?: [], 'is_dir'));
    }

    private function loadModuleRoutes(string $modulePath): void
    {
        foreach (glob($modulePath . '/routes/*.php') ?: [] as $routeFile) {
            Route::middleware('api')->group($routeFile);
        }
    }
}
