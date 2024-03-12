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

use LaravelJsonApi\Core\Document\ErrorList;

interface Result
{
    /**
     * Is this a success result?
     *
     * @return bool
     */
    public function didSucceed(): bool;

    /**
     * Is this a failure result?
     *
     * @return bool
     */
    public function didFail(): bool;

    /**
     * Get the result errors.
     *
     * @return ErrorList
     */
    public function errors(): ErrorList;
}
