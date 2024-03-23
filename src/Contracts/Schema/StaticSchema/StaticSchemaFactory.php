<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Schema\StaticSchema;

use Generator;
use LaravelJsonApi\Contracts\Schema\Schema;

interface StaticSchemaFactory
{
    /**
     * Make static schemas for the provided schema classes.
     *
     * @param iterable<class-string<Schema>> $schemas
     * @return Generator<StaticSchema>
     */
    public function make(iterable $schemas): Generator;
}