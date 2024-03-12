<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\Query;

use Illuminate\Http\Request;
use Illuminate\Support\ValidatedInput;
use LaravelJsonApi\Contracts\Query\QueryParameters as QueryParametersContract;
use LaravelJsonApi\Core\Query\Input\Query as QueryInput;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Support\Contracts;
use LaravelJsonApi\Core\Values\ResourceType;

abstract class Query
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
     * @var QueryParametersContract|null
     */
    private ?QueryParametersContract $validatedParameters = null;

    /**
     * @return QueryInput
     */
    abstract public function input(): QueryInput;

    /**
     * Query constructor
     *
     * @param Request|null $request
     */
    public function __construct(private readonly ?Request $request)
    {
    }

    /**
     * Get the primary resource type.
     *
     * @return ResourceType
     */
    public function type(): ResourceType
    {
        return $this->input()->type;
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
