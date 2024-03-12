<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\FetchRelationship;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Actions\Input\IsRelatable;
use LaravelJsonApi\Core\Http\Actions\Input\Relatable;
use LaravelJsonApi\Core\Query\Input\QueryRelationship;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class FetchRelationshipActionInput extends ActionInput implements IsRelatable
{
    use Relatable;

    /**
     * @var QueryRelationship|null
     */
    private ?QueryRelationship $query = null;

    /**
     * FetchRelationshipActionInput constructor
     *
     * @param Request $request
     * @param ResourceType $type
     * @param ResourceId $id
     * @param string $fieldName
     * @param object|null $model
     */
    public function __construct(
        Request $request,
        ResourceType $type,
        ResourceId $id,
        string $fieldName,
        object $model = null,
    ) {
        parent::__construct($request, $type);
        $this->id = $id;
        $this->fieldName = $fieldName;
        $this->model = $model;
    }

    /**
     * @return QueryRelationship
     */
    public function query(): QueryRelationship
    {
        if ($this->query) {
            return $this->query;
        }

        return $this->query = new QueryRelationship(
            $this->type,
            $this->id,
            $this->fieldName,
            (array) $this->request->query(),
        );
    }
}
