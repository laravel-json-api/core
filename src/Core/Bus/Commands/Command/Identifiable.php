<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Command;

use LaravelJsonApi\Core\Store\LazyModel;

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
        assert($this->model === null, 'Not expecting existing model to be replaced on a command.');

        $copy = clone $this;
        $copy->model = $model;

        return $copy;
    }

    /**
     * Get the model for the command.
     *
     * @return object|null
     */
    public function model(): ?object
    {
        if ($this->model instanceof LazyModel) {
            return $this->model->get();
        }

        return $this->model;
    }

    /**
     * Get the model for the command.
     *
     * @return object
     */
    public function modelOrFail(): object
    {
        $model = $this->model();

        assert($model !== null, 'Expecting a model to be set on the command.');

        return $model;
    }
}
