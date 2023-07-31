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

namespace LaravelJsonApi\Core\Bus\Commands\Concerns;

use RuntimeException;

trait Identifiable
{
    /**
     * @var object|null
     */
    private ?object $model = null;

    /**
     * Return a new instance with the model set, if known.
     *
     * @param object|null $model
     * @return static
     */
    public function withModel(?object $model): static
    {
        $copy = clone $this;
        $copy->model = $model;

        return $copy;
    }

    /**
     * Get the model for the query.
     *
     * @return object|null
     */
    public function model(): ?object
    {
        return $this->model;
    }

    /**
     * Get the model for the query.
     *
     * @return object
     */
    public function modelOrFail(): object
    {
        if ($this->model !== null) {
            return $this->model;
        }

        throw new RuntimeException('Expecting a model to be set on the query.');
    }
}
