<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Store;

use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Query\Input\WillQueryOne;

class StoreActionInput extends ActionInput
{
    /**
     * @var Create|null
     */
    private ?Create $operation = null;

    /**
     * @var WillQueryOne|null
     */
    private ?WillQueryOne $query = null;

    /**
     * Return a new instance with the store operation set.
     *
     * @param Create $operation
     * @return $this
     */
    public function withOperation(Create $operation): self
    {
        $copy = clone $this;
        $copy->operation = $operation;

        return $copy;
    }

    /**
     * @return Create
     */
    public function operation(): Create
    {
        if ($this->operation) {
            return $this->operation;
        }

        throw new \LogicException('No store operation set on store action.');
    }

    /**
     * @return WillQueryOne
     */
    public function query(): WillQueryOne
    {
        if ($this->query) {
            return $this->query;
        }

        return $this->query = new WillQueryOne(
            $this->type,
            (array) $this->request->query(),
        );
    }
}
