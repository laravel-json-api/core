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

namespace LaravelJsonApi\Core\Http\Actions\DetachRelationship;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Actions\Input\IsRelatable;
use LaravelJsonApi\Core\Http\Actions\Input\Relatable;

class DetachRelationshipActionInput extends ActionInput implements IsRelatable
{
    use Relatable;

    /**
     * @var UpdateToMany|null
     */
    private UpdateToMany|null $operation = null;

    /**
     * DetachRelationshipActionInput constructor
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
     * Return a new instance with the detach relationship operation set.
     *
     * @param UpdateToMany $operation
     * @return $this
     */
    public function withOperation(UpdateToMany $operation): self
    {
        assert($operation->isDetachingRelationship(), 'Expecting a detach relationship operation.');

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
}
