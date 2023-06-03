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

use LaravelJsonApi\Core\Extensions\Atomic\Operations\Store;
use LaravelJsonApi\Core\Http\Actions\Action;
use LaravelJsonApi\Core\Support\ContractException;

class StoreAction extends Action
{
    /**
     * @var Store|null
     */
    private ?Store $operation = null;

    /**
     * Return a new instance with the store operation set.
     *
     * @param Store $operation
     * @return $this
     */
    public function withOperation(Store $operation): self
    {
        $copy = clone $this;
        $copy->operation = $operation;

        return $copy;
    }

    /**
     * @return Store
     */
    public function operation(): Store
    {
        if ($this->operation) {
            return $this->operation;
        }

        throw new ContractException('No store operation set on store action.');
    }
}
