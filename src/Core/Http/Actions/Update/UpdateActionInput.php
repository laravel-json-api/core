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

namespace LaravelJsonApi\Core\Http\Actions\Update;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Actions\Input\Identifiable;
use LaravelJsonApi\Core\Http\Actions\Input\IsIdentifiable;

class UpdateActionInput extends ActionInput implements IsIdentifiable
{
    use Identifiable;

    /**
     * @var Update|null
     */
    private ?Update $operation = null;

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
        object $model = null,
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
}