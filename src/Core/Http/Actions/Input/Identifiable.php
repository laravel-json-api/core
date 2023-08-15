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

namespace LaravelJsonApi\Core\Http\Actions\Input;

use LaravelJsonApi\Core\Document\Input\Values\ResourceId;

trait Identifiable
{
    /**
     * @var ResourceId
     */
    private readonly ResourceId $id;

    /**
     * @var object|null
     */
    private ?object $model = null;

    /**
     * @inheritDoc
     */
    public function id(): ResourceId
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function model(): ?object
    {
        return $this->model;
    }

    /**
     * @inheritDoc
     */
    public function modelOrFail(): object
    {
        assert($this->model !== null, 'Expecting a model to be set.');

        return $this->model;
    }

    /**
     * @inheritDoc
     */
    public function withModel(object $model): static
    {
        assert($this->model === null, 'Cannot set a model when one is already set.');

        $copy = clone $this;
        $copy->model = $model;

        return $copy;
    }
}