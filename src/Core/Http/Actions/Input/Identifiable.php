<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Input;

use LaravelJsonApi\Core\Values\ResourceId;

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
