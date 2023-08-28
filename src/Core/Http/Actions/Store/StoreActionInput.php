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
