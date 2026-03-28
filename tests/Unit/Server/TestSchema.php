<?php

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Server;

use LaravelJsonApi\Core\Schema\Schema;

class TestSchema extends Schema
{
    public static string $model = 'test';

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [];
    }
}
