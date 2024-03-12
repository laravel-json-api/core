<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
     */
    public function id(): ResourceId
    {
        $id = $this->operation->ref()->id;

        assert($id !== null, 'Expecting an update relationship operation with a ref that has an id.');

        return $id;
    }

    /**
     * @inheritDoc
     */
    public function fieldName(): string
    {
        return $this->operation->getFieldName();
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
