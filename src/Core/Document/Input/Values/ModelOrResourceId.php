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

namespace LaravelJsonApi\Core\Document\Input\Values;

class ModelOrResourceId
{
    /**
     * @var object|null
     */
    private ?object $model;

    /**
     * @var ResourceId|null
     */
    private ?ResourceId $id;

    /**
     * Fluent constructor.
     *
     * @param object|string $modelOrResourceId
     * @return static
     */
    public static function make(object|string $modelOrResourceId): self
    {
        return new self($modelOrResourceId);
    }

    /**
     * ModelOrResourceId constructor
     *
     * @param object|string $modelOrResourceId
     */
    public function __construct(object|string $modelOrResourceId)
    {
        if ($modelOrResourceId instanceof ResourceId || is_string($modelOrResourceId)) {
            $this->id = ResourceId::cast($modelOrResourceId);
            $this->model = null;
            return;
        }

        $this->model = $modelOrResourceId;
        $this->id = null;
    }

    /**
     * @return object|null
     */
    public function model(): ?object
    {
        return $this->model;
    }

    /**
     * @return object
     */
    public function modelOrFail(): object
    {
        assert($this->model !== null, 'Expecting a model to be set.');

        return $this->model;
    }

    /**
     * @return ResourceId|null
     */
    public function id(): ?ResourceId
    {
        return $this->id;
    }
}