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

namespace LaravelJsonApi\Core\Bus\Commands\Destroy;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\DestroyImplementation;
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Command\Identifiable;
use LaravelJsonApi\Core\Bus\Commands\Command\IsIdentifiable;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;

class DestroyCommand extends Command implements IsIdentifiable
{
    use Identifiable;

    /**
     * @var DestroyImplementation|null
     */
    private ?DestroyImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param Delete $operation
     * @return self
     */
    public static function make(?Request $request, Delete $operation): self
    {
        return new self($request, $operation);
    }

    /**
     * DestroyCommand constructor
     *
     * @param Request|null $request
     * @param Delete $operation
     */
    public function __construct(?Request $request, private readonly Delete $operation)
    {
        parent::__construct($request);
    }

    /**
     * @inheritDoc
     * @TODO support getting resource type from a href.
     */
    public function type(): ResourceType
    {
        $type = $this->operation->ref()?->type;

        assert($type !== null, 'Expecting a delete operation with a ref.');

        return $type;
    }

    /**
     * @inheritDoc
     * @TODO support getting resource id from a href.
     */
    public function id(): ResourceId
    {
        $id = $this->operation->ref()?->id;

        assert($id !== null, 'Expecting a delete operation with a ref that has an id.');

        return $id;
    }

    /**
     * @inheritDoc
     */
    public function operation(): Delete
    {
        return $this->operation;
    }

    /**
     * Set the hooks implementation.
     *
     * @param DestroyImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?DestroyImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return DestroyImplementation|null
     */
    public function hooks(): ?DestroyImplementation
    {
        return $this->hooks;
    }
}
