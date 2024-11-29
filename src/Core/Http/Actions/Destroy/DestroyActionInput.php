<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Destroy;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Actions\Input\Identifiable;
use LaravelJsonApi\Core\Http\Actions\Input\IsIdentifiable;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class DestroyActionInput extends ActionInput implements IsIdentifiable
{
    use Identifiable;

    /**
     * @var Delete|null
     */
    private ?Delete $operation = null;

    /**
     * DestroyActionInput constructor
     *
     * @param Request $request
     * @param ResourceType $type
     * @param ResourceId $id
     * @param object|null $model
     */
    public function __construct(
        Request $request,
        ResourceType $type,
        ResourceId $id,
        ?object $model = null,
    ) {
        parent::__construct($request, $type);
        $this->id = $id;
        $this->model = $model;
    }

    /**
     * Return a new instance with the delete operation set.
     *
     * @param Delete $operation
     * @return $this
     */
    public function withOperation(Delete $operation): self
    {
        $copy = clone $this;
        $copy->operation = $operation;

        return $copy;
    }

    /**
     * @return Delete
     */
    public function operation(): Delete
    {
        assert($this->operation !== null, 'Expecting a delete operation to be set.');

        return $this->operation;
    }
}
