<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
