<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
