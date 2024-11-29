<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Update;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Actions\Input\Identifiable;
use LaravelJsonApi\Core\Http\Actions\Input\IsIdentifiable;
use LaravelJsonApi\Core\Query\Input\QueryOne;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class UpdateActionInput extends ActionInput implements IsIdentifiable
{
    use Identifiable;

    /**
     * @var Update|null
     */
    private ?Update $operation = null;

    /**
     * @var QueryOne|null
     */
    private ?QueryOne $query = null;

    /**
     * UpdateActionInput constructor
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
     * Return a new instance with the update operation set.
     *
     * @param Update $operation
     * @return $this
     */
    public function withOperation(Update $operation): self
    {
        $copy = clone $this;
        $copy->operation = $operation;

        return $copy;
    }

    /**
     * @return Update
     */
    public function operation(): Update
    {
        assert($this->operation !== null, 'Expecting an update operation to be set.');

        return $this->operation;
    }

    /**
     * @return QueryOne
     */
    public function query(): QueryOne
    {
        if ($this->query) {
            return $this->query;
        }

        return $this->query = new QueryOne(
            $this->type,
            $this->id,
            (array) $this->request->query(),
        );
    }
}
