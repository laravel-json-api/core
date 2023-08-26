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

namespace LaravelJsonApi\Core\Store;

use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class LazyModel
{
    /**
     * @var object|null
     */
    private ?object $model = null;

    /**
     * @var bool
     */
    private bool $loaded = false;

    /**
     * LazyModel constructor
     *
     * @param StoreContract $store
     * @param ResourceType $type
     * @param ResourceId $id
     */
    public function __construct(
        private readonly StoreContract $store,
        private readonly ResourceType $type,
        private readonly ResourceId $id,
    ) {
    }

    /**
     * @return object|null
     */
    public function get(): ?object
    {
        if ($this->loaded === true) {
            return $this->model;
        }

        $this->model = $this->store->find($this->type, $this->id);
        $this->loaded = true;

        return $this->model;
    }

    /**
     * @return object
     */
    public function getOrFail(): object
    {
        $model = $this->get();

        assert($model !== null, sprintf(
            'Resource of type %s and id %s does not exist.',
            $this->type,
            $this->id,
        ));

        return $model;
    }

    /**
     * @param LazyModel $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->store === $other->store
            && $this->type->equals($other->type)
            && $this->id->equals($other->id);
    }
}
