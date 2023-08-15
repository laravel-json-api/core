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

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;

class StoreActionInputFactory
{
    /**
     * Make an input object for a store action.
     *
     * @param Request $request
     * @param ResourceType|string $type
     * @return StoreActionInput
     */
    public function make(Request $request, ResourceType|string $type): StoreActionInput
    {
        return new StoreActionInput(
            $request,
            ResourceType::cast($type),
        );
    }
}