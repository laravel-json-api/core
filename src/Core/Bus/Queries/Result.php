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

namespace LaravelJsonApi\Core\Bus\Queries;

use LaravelJsonApi\Contracts\Bus\Result as ResultContract;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;

class Result implements ResultContract
{
    /**
     * @var ErrorList|null
     */
    private ?ErrorList $errors = null;

    /**
     * Return a success result.
     *
     * @param Payload $payload
     * @param QueryParameters $parameters
     * @return self
     */
    public static function ok(Payload $payload, QueryParameters $parameters): self
    {
        return new self(true, $payload, $parameters);
    }

    /**
     * Return a failure result.
     *
     * @param ErrorList|Error $errorOrErrors
     * @return self
     */
    public static function failed(ErrorList|Error $errorOrErrors): self
    {
        $result = new self(false);
        $result->errors = ErrorList::cast($errorOrErrors);

        return $result;
    }

    /**
     * Result constructor
     *
     * @param bool $success
     * @param Payload|null $payload
     * @param QueryParameters|null $query
     */
    private function __construct(
        private readonly bool $success,
        private readonly ?Payload $payload = null,
        private readonly ?QueryParameters $query = null,
    ) {
    }

    /**
     * @return Payload
     */
    public function payload(): Payload
    {
        if ($this->payload !== null) {
            return $this->payload;
        }

        throw new \LogicException('Cannot get payload from a failed query result.');
    }

    /**
     * @return QueryParameters
     */
    public function query(): QueryParameters
    {
        if ($this->query !== null) {
            return $this->query;
        }

        throw new \LogicException('Cannot get payload from a failed query result.');
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
        return !$this->didSucceed();
    }

    /**
     * @inheritDoc
     */
    public function errors(): ErrorList
    {
        if ($this->errors) {
            return $this->errors;
        }

        return $this->errors = new ErrorList();
    }
}
