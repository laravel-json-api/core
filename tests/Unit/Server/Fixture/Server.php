<?php

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Server\Fixture;

final class Server extends \LaravelJsonApi\Core\Server\Server
{
    public function serving(): void
    {
    }

    protected function allSchemas(): array
    {
        return [];
    }
}
