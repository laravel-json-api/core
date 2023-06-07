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

use Illuminate\Http\Request;
use Illuminate\Support\ValidatedInput;
use LaravelJsonApi\Contracts\Query\QueryParameters as QueryParametersContract;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Support\Contracts;

abstract class Query
{
    /**
     * @var ResourceType
     */
    private readonly ResourceType $type;

    /**
     * @var bool
     */
    private bool $authorize = true;

    /**
     * @var array|null
     */
    private ?array $parameters = null;

    /**
     * @var bool
     */
    private bool $validate = true;

    /**
     * @var array|null
     */
    private ?array $validated = null;

    /**
     * @var QueryParametersContract|null
     */
    private ?QueryParametersContract $validatedParameters = null;

    /**
     * Query constructor
     *
     * @param Request|null $request
     * @param ResourceType|string $type
     */
    public function __construct(
        private readonly ?Request $request,
        ResourceType|string $type,
    ) {
        $this->type = ResourceType::cast($type);
    }

    /**
     * Get the primary resource type.
     *
     * @return ResourceType
     */
    public function type(): ResourceType
    {
        return $this->type;
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
     * Set the raw query parameters.
     *
     * @param array $params
     * @return $this
     */
    public function withParameters(array $params): static
    {
        $copy = clone $this;
        $copy->parameters = $params;

        return $copy;
    }

    /**
     * Get the raw query parameters.
     *
     * @return array
     */
    public function parameters(): array
    {
        if ($this->parameters === null) {
            $parameters = $this->request?->query();
            $this->parameters = $parameters ?? [];
        }

        return $this->parameters;
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
     * @param QueryParametersContract|array $data
     * @return static
     */
    public function withValidated(QueryParametersContract|array $data): static
    {
        $copy = clone $this;

        if ($data instanceof QueryParametersContract) {
            $copy->validated = $data->toQuery();
            $copy->validatedParameters = $data;
            return $copy;
        }

        $copy->validated = $data;
        $copy->validatedParameters = null;

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
        Contracts::assert($this->validated !== null, 'No validated query parameters set.');

        return $this->validated ?? [];
    }

    /**
     * @return ValidatedInput
     */
    public function safe(): ValidatedInput
    {
        return new ValidatedInput($this->validated());
    }

    /**
     * @return QueryParametersContract
     */
    public function toQueryParams(): QueryParametersContract
    {
        if ($this->validatedParameters) {
            return $this->validatedParameters;
        }

        return $this->validatedParameters = QueryParameters::fromArray(
            $this->validated(),
        );
    }
}
