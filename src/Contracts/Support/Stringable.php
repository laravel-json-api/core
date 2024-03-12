<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Support;

use Stringable as BaseStringable;

interface Stringable extends BaseStringable
{
    /**
     * Fluent to-string method.
     *
     * @return string
     */
    public function toString(): string;
}