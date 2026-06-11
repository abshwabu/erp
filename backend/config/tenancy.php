<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Tenancy\Finders\DomainTenantFinder;
use App\Modules\Core\Tenancy\Finders\SubdomainTenantFinder;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager;

// Collect both top-level and /tenant sub-directory migration paths from all modules
$moduleMigrationPaths = array_values(array_filter(
    array_merge(
        glob(app_path('Modules/*/database/migrations'), GLOB_ONLYDIR) ?: [],
        glob(app_path('Modules/*/database/migrations/tenant'), GLOB_ONLYDIR) ?: []
    ),
    'is_dir'
));

return [
    'tenant_model' => Tenant::class,
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,
    'domain_model' => Stancl\Tenancy\Database\Models\Domain::class,
    'central_domains' => [
        '127.0.0.1',
        'localhost',
    ],
    'tenant_finders' => [
        SubdomainTenantFinder::class,
        DomainTenantFinder::class,
    ],
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'pgsql'),
        'template_tenant_connection' => null,
        'prefix' => 'tenant',
        'suffix' => '',
        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'mariadb' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql' => PostgreSQLSchemaManager::class,
        ],
    ],
    'cache' => [
        'tag_base' => 'tenant',
    ],
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],
    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [],
    ],
    'features' => [],
    'routes' => true,
    'migration_parameters' => [
        '--force' => true,
        '--path' => array_merge([database_path('migrations/tenant')], $moduleMigrationPaths),
        '--realpath' => true,
    ],
    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],
];
