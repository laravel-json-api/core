<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\UpdateRelationship;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\UpdateRelationshipImplementation;
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Command\HasQuery;
use LaravelJsonApi\Core\Bus\Commands\Command\Identifiable;
use LaravelJsonApi\Core\Bus\Commands\Command\IsRelatable;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToOne;
use LaravelJsonApi\Core\Support\Contracts;
use LaravelJsonApi\Core\Values\ResourceId;

class UpdateRelationshipCommand extends Command implements IsRelatable
{
    use Identifiable;
    use HasQuery;

    /**
     * @var UpdateRelationshipImplementation|null
     */
    private ?UpdateRelationshipImplementation $hooks = null;

    /**
     * Fluent constructor
     *
     * @param Request|null $request
     * @param UpdateToOne|UpdateToMany $operation
     * @return self
     */
    public static function make(?Request $request, UpdateToOne|UpdateToMany $operation): self
    {
        return new self($request, $operation);
    }

    /**
     * UpdateRelationshipCommand constructor
     *
     * @param Request|null $request
     * @param UpdateToOne|UpdateToMany $operation
     */
    public function __construct(?Request $request, private readonly UpdateToOne|UpdateToMany $operation)
    {
        Contracts::assert(
            $this->operation->isUpdatingRelationship(),
            'Expecting a to-many operation that is to update (replace) the whole relationship.',
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
    public function operation(): UpdateToOne|UpdateToMany
    {
        return $this->operation;
    }

    /**
     * @return bool
     */
    public function toOne(): bool
    {
        return $this->operation instanceof UpdateToOne;
    }

    /**
     * @return bool
     */
    public function toMany(): bool
    {
        return $this->operation instanceof UpdateToMany;
    }

    /**
     * Set the hooks implementation.
     *
     * @param UpdateRelationshipImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?UpdateRelationshipImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return UpdateRelationshipImplementation|null
     */
    public function hooks(): ?UpdateRelationshipImplementation
    {
        return $this->hooks;
    }
}
