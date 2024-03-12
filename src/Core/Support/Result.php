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

use LaravelJsonApi\Contracts\Support\Result as ResultContract;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;

class Result implements ResultContract
{
    /**
     * Return a success result.
     *
     * @return self
     */
    public static function ok(): self
    {
        return new self(true);
    }

    /**
     * Return a failed result.
     *
     * @param ErrorList|Error $errors
     * @return self
     */
    public static function failed(ErrorList|Error $errors = new ErrorList()): self
    {
        return new self(false, ErrorList::cast($errors));
    }

    /**
     * Result constructor
     *
     * @param bool $success
     * @param ErrorList|null $errors
     */
    private function __construct(
        private readonly bool $success,
        private readonly ?ErrorList $errors = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function didSucceed(): bool
    {
        return $this->success;
    }

    /**
     * @inheritDoc
     */
    public function didFail(): bool
    {
        return !$this->success;
    }

    /**
     * @inheritDoc
     */
    public function errors(): ErrorList
    {
        return $this->errors ?? new ErrorList();
    }
}
