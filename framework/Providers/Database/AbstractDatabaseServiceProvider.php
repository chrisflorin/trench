<?php

namespace Trench\Providers\Database;

use Trench\Providers\AbstractServiceProvider;

abstract class AbstractDatabaseServiceProvider extends AbstractServiceProvider
{
    protected function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations/common');
    }
}
