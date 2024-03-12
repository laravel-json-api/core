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

use Illuminate\Contracts\Container\Container;
use LaravelJsonApi\Core\Support\ContainerResolver;
use PHPUnit\Framework\TestCase;

class ContainerResolverTest extends TestCase
{

    public function test(): void
    {
        $mock = $this->createMock(Container::class);

        $resolver = new ContainerResolver(
            static fn() => $mock
        );

        $this->assertSame($mock, $resolver->instance());
    }
}
