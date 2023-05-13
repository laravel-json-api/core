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

namespace LaravelJsonApi\Core\Operations\Commands\Store;

use LaravelJsonApi\Contracts\Operations\Commands\Store\StoreCommandHandler as StoreCommandHandlerContract;
use LaravelJsonApi\Contracts\Store\Store;

class StoreCommandHandler implements StoreCommandHandlerContract
{
    /**
     * @param Store $store
     */
    public function __construct(private readonly Store $store)
    {
    }

    /**
     * @inheritDoc
     */
    public function execute(StoreCommand $command): StoreResult
    {
        $resource = $this->store
            ->create($command->type())
            ->maybeWithRequest($command->request())
            ->store($command->validated());

        return new StoreResult($resource);
    }
}
