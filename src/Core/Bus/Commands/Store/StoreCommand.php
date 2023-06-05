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

namespace LaravelJsonApi\Core\Bus\Commands\Store;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\StoreImplementation;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Store;
use LaravelJsonApi\Core\Bus\Commands\Command;

class StoreCommand extends Command
{
    /**
     * @var StoreImplementation|null
     */
    private ?StoreImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param Store $operation
     * @return self
     */
    public static function make(?Request $request, Store $operation): self
    {
        return new self($request, $operation);
    }

    /**
     * StoreCommand constructor
     *
     * @param Request|null $request
     * @param Store $operation
     */
    public function __construct(
        ?Request $request,
        private readonly Store $operation
    ) {
        parent::__construct($request);
    }

    /**
     * @inheritDoc
     */
    public function type(): ResourceType
    {
        return $this->operation->data->type;
    }

    /**
     * @inheritDoc
     */
    public function operation(): Store
    {
        return $this->operation;
    }

    /**
     * Set the hooks implementation.
     *
     * @param StoreImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?StoreImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return StoreImplementation|null
     */
    public function hooks(): ?StoreImplementation
    {
        return $this->hooks;
    }
}
