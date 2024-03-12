<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Support;

use Illuminate\Contracts\Foundation\Application;
use LaravelJsonApi\Core\Support\AppResolver;
use PHPUnit\Framework\TestCase;

class AppResolverTest extends TestCase
{
    public function test(): void
    {
        $mock = $this->createMock(Application::class);

        $resolver = new AppResolver(
            static fn() => $mock
        );

        $this->assertSame($mock, $resolver->instance());
        $this->assertSame($mock, $resolver->container()->instance());
    }
}
