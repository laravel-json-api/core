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

namespace LaravelJsonApi\Core\Operations\Result;

use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Json\Hash;

trait ResultTrait
{
    /**
     * @var bool
     */
    private bool $success;

    /**
     * @var ErrorList|null
     */
    private ?ErrorList $errors = null;

    /**
     * @var Hash|null
     */
    private ?Hash $meta = null;

    /**
     * @return bool
     */
    public function didSucceed(): bool
    {
        return $this->success;
    }

    /**
     * @return bool
     */
    public function didFail(): bool
    {
        return !$this->success;
    }

    /**
     * @return ErrorList
     */
    public function getErrors(): ErrorList
    {
        if ($this->errors) {
            return $this->errors;
        }

        return $this->errors = new ErrorList();
    }

    /**
     * @return Hash
     */
    public function getMeta(): Hash
    {
        if ($this->meta) {
            return $this->meta;
        }

        return $this->meta = new Hash();
    }

    /**
     * @param Hash|array $meta
     * @return ResultTrait
     */
    public function withMeta(Hash|array $meta): self
    {
        $original = $this->meta ? clone $this->meta : new Hash();

        $copy = clone $this;
        $copy->meta = $original->merge($meta);

        return $copy;
    }
}
