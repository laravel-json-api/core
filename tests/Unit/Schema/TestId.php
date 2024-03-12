<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Schema;

use LaravelJsonApi\Contracts\Schema\ID;
use LaravelJsonApi\Contracts\Schema\IdEncoder;

interface TestId extends ID, IdEncoder
{
}
