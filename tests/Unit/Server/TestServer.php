<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Server;

use LaravelJsonApi\Core\Server\Server as ServerContract;

class TestServer extends ServerContract
{
    /**
     * @return void
     */
    public function serving(): void
    {
        // no-op
    }

    /**
     * @inheritDoc
     */
    protected function allSchemas(): array
    {
        return [];
    }
}
