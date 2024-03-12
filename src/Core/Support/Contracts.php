<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Support;

final class Contracts
{
    /**
     * @param bool $precondition
     * @param string $message
     * @return void
     */
    public static function assert(bool $precondition, string $message = ''): void
    {
        if ($precondition === false) {
            throw new \LogicException($message);
        }
    }

    /**
     * Contracts constructor
     */
    private function __construct()
    {
    }
}
