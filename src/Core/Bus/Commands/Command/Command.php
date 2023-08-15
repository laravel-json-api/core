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

namespace LaravelJsonApi\Core\Bus\Commands\Command;

use Illuminate\Http\Request;
use Illuminate\Support\ValidatedInput;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Operation;
use LaravelJsonApi\Core\Support\Contracts;

abstract class Command
{
    /**
     * @var bool
     */
    private bool $authorize = true;

    /**
     * @var bool
     */
    private bool $validate = true;

    /**
     * @var array|null
     */
    private ?array $validated = null;

    /**
     * @var QueryParameters|null
     */
    private ?QueryParameters $queryParameters = null;

    /**
     * Get the primary resource type.
     *
     * @return ResourceType
     */
    abstract public function type(): ResourceType;

    /**
     * Get the operation object.
     *
     * @return Operation
     */
    abstract public function operation(): Operation;

    /**
     * Command constructor
     *
     * @param Request|null $request
     */
    public function __construct(private readonly ?Request $request)
    {
    }

    /**
     * Get the HTTP request, if the command is being executed during a HTTP request.
     *
     * @return Request|null
     */
    public function request(): ?Request
    {
        return $this->request;
    }

    /**
     * Set the query parameters that will be used when processing the result payload.
     *
     * @param QueryParameters|null $query
     * @return $this
     */
    public function withQuery(?QueryParameters $query): static
    {
        $copy = clone $this;
        $copy->queryParameters = $query;

        return $copy;
    }

    /**
     * @return QueryParameters|null
     */
    public function query(): ?QueryParameters
    {
        return $this->queryParameters;
    }

    /**
     * @return bool
     */
    public function mustAuthorize(): bool
    {
        return $this->authorize;
    }

    /**
     * @return static
     */
    public function skipAuthorization(): static
    {
        $copy = clone $this;
        $copy->authorize = false;

        return $copy;
    }

    /**
     * @return bool
     */
    public function mustValidate(): bool
    {
        return $this->validate === true && $this->validated === null;
    }

    /**
     * Skip validation - use if the input data is from a "trusted" source.
     *
     * @return static
     */
    public function skipValidation(): static
    {
        $copy = clone $this;
        $copy->validate = false;

        return $copy;
    }

    /**
     * @param array $data
     * @return static
     */
    public function withValidated(array $data): static
    {
        $copy = clone $this;
        $copy->validated = $data;

        return $copy;
    }

    /**
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->validated !== null;
    }

    /**
     * @return bool
     */
    public function isNotValidated(): bool
    {
        return !$this->isValidated();
    }

    /**
     * @return array
     */
    public function validated(): array
    {
        Contracts::assert($this->validated !== null, 'No validated data set.');

        return $this->validated ?? [];
    }

    /**
     * @return ValidatedInput
     */
    public function safe(): ValidatedInput
    {
        return new ValidatedInput($this->validated());
    }
}
