<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager as BasePostgreSQLSchemaManager;

class PostgreSQLSchemaManager extends BasePostgreSQLSchemaManager
{
    public function makeConnectionConfig(array $baseConfig, string $databaseName): array
    {
        // Include 'public' schema in search_path so central tables (tenants, domains, plans) are always accessible
        $baseConfig['search_path'] = "{$databaseName}, public";

        return $baseConfig;
    }
}
