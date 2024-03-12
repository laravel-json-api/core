<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Command;

use Illuminate\Http\Request;
use Illuminate\Support\ValidatedInput;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Operation;
use LaravelJsonApi\Core\Support\Contracts;
use LaravelJsonApi\Core\Values\ResourceType;

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
     * @return ResourceType
     */
    public function type(): ResourceType
    {
        return $this->operation()->type();
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
