<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        config(['database.connections.pgsql.database' => 'erp_testing']);
        \Illuminate\Support\Facades\DB::purge('pgsql');

        return $app;
    }
}
