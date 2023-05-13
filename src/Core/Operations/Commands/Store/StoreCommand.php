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

namespace LaravelJsonApi\Core\Operations\Commands\Store;

use Illuminate\Http\Request;

class StoreCommand
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
     * @param string $type
     * @param array $data
     */
    public function __construct(
        private readonly string $type,
        private readonly array $data,
        private readonly ?Request $request = null,
    ) {
        if (empty($this->type)) {
            throw new \InvalidArgumentException('Expecting a non-empty type.');
        }

        $actual = $this->data['type'] ?? null;

        if ($this->type !== $actual) {
            throw new \InvalidArgumentException('Expecting type in data to match the provided type.');
        }
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * @return Request|null
     */
    public function request(): ?Request
    {
        return $this->request;
    }

    /**
     * @return Request
     */
    public function requestOrFail(): Request
    {
        if ($this->request) {
            return $this->request;
        }

        throw new \LogicException('Command does not have a request.');
    }

    /**
     * @return bool
     */
    public function hasRequest(): bool
    {
        return $this->request !== null;
    }

    /**
     * @return bool
     */
    public function mustAuthorize(): bool
    {
        return $this->authorize;
    }

    /**
     * @return $this
     */
    public function skipAuthorization(): self
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
        return $this->validate && $this->validated === null;
    }

    /**
     * @param array $data
     * @return self
     */
    public function withValidated(array $data): self
    {
        $copy = clone $this;
        $copy->validated = $data;

        return $copy;
    }

    /**
     * @return self
     */
    public function skipValidation(): self
    {
        $copy = clone $this;
        $copy->validate = false;

        return $copy;
    }

    /**
     * @return array
     */
    public function validated(): array
    {
        if ($this->validated !== null) {
            return $this->validated;
        }

        throw new \LogicException('No validated data set on store command.');
    }

    /**
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->validated !== null;
    }
}
