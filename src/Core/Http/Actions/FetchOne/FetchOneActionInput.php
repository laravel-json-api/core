<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\FetchOne;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Actions\Input\Identifiable;
use LaravelJsonApi\Core\Http\Actions\Input\IsIdentifiable;
use LaravelJsonApi\Core\Query\Input\QueryOne;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class FetchOneActionInput extends ActionInput implements IsIdentifiable
{
    use Identifiable;

    /**
     * @var QueryOne|null
     */
    private ?QueryOne $query = null;

    /**
     * FetchOneActionInput constructor
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
