<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\AttachRelationship;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Actions\Input\IsRelatable;
use LaravelJsonApi\Core\Http\Actions\Input\Relatable;
use LaravelJsonApi\Core\Query\Input\QueryRelationship;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class AttachRelationshipActionInput extends ActionInput implements IsRelatable
{
    use Relatable;

    /**
     * @var UpdateToMany|null
     */
    private ?UpdateToMany $operation = null;

    /**
     * @var QueryRelationship|null
     */
    private ?QueryRelationship $query = null;

    /**
     * AttachRelationshipActionInput constructor
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
        ?object $model = null,
    ) {
        parent::__construct($request, $type);
        $this->id = $id;
        $this->fieldName = $fieldName;
        $this->model = $model;
    }

    /**
     * Return a new instance with the attach relationship operation set.
     *
     * @param UpdateToMany $operation
     * @return $this
     */
    public function withOperation(UpdateToMany $operation): self
    {
        assert($operation->isAttachingRelationship(), 'Expecting an attach relationship operation.');

        $copy = clone $this;
        $copy->operation = $operation;

        return $copy;
    }

    /**
     * @return UpdateToMany
     */
    public function operation(): UpdateToMany
    {
        assert($this->operation !== null, 'Expecting an update relationship operation to be set.');

        return $this->operation;
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
