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

namespace LaravelJsonApi\Core\Http\Actions\FetchMany;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;

class FetchManyActionInputFactory
{
    /**
     * Make an input object for a fetch-many action.
     *
     * @param Request $request
     * @param ResourceType|string $type
     * @return FetchManyActionInput
     */
    public function make(Request $request, ResourceType|string $type): FetchManyActionInput
    {
        return new FetchManyActionInput(
            $request,
            ResourceType::cast($type),
        );
    }
}