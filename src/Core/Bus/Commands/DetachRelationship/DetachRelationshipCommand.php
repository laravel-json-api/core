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

namespace LaravelJsonApi\Core\Bus\Commands\DetachRelationship;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\DetachRelationshipImplementation;
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Command\HasQuery;
use LaravelJsonApi\Core\Bus\Commands\Command\Identifiable;
use LaravelJsonApi\Core\Bus\Commands\Command\IsRelatable;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Support\Contracts;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class DetachRelationshipCommand extends Command implements IsRelatable
{
    use Identifiable;
    use HasQuery;

    /**
     * @var DetachRelationshipImplementation|null
     */
    private ?DetachRelationshipImplementation $hooks = null;

    /**
     * Fluent constructor
     *
     * @param Request|null $request
     * @param UpdateToMany $operation
     * @return self
     */
    public static function make(?Request $request, UpdateToMany $operation): self
    {
        return new self($request, $operation);
    }

    /**
     * DetachRelationshipCommand constructor
     *
     * @param Request|null $request
     * @param UpdateToMany $operation
     */
    public function __construct(?Request $request, private readonly UpdateToMany $operation)
    {
        Contracts::assert(
            $this->operation->isDetachingRelationship(),
            'Expecting a to-many operation that is to detach resources from a relationship.',
        );

        parent::__construct($request);
    }

    /**
     * @inheritDoc
     * @TODO support operation with a href.
     */
    public function type(): ResourceType
    {
        $type = $this->operation->ref()?->type;

        assert($type !== null, 'Expecting an update relationship operation with a ref.');

        return $type;
    }

    /**
     * @inheritDoc
     * @TODO support operation with a href
     */
    public function id(): ResourceId
    {
        $id = $this->operation->ref()?->id;

        assert($id !== null, 'Expecting an update relationship operation with a ref that has an id.');

        return $id;
    }

    /**
     * @inheritDoc
     */
    public function fieldName(): string
    {
        $fieldName = $this->operation->ref()?->relationship ?? $this->operation->href()?->getRelationshipName();

        assert(
            is_string($fieldName),
            'Expecting update relationship operation to have a field name.',
        );

        return $fieldName;
    }

    /**
     * @inheritDoc
     */
    public function operation(): UpdateToMany
    {
        return $this->operation;
    }

    /**
     * Set the hooks implementation.
     *
     * @param DetachRelationshipImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?DetachRelationshipImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return DetachRelationshipImplementation|null
     */
    public function hooks(): ?DetachRelationshipImplementation
    {
        return $this->hooks;
    }
}
