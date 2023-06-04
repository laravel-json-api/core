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

namespace LaravelJsonApi\Contracts\Http\Controllers\Hooks;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters;

interface StoreImplementation extends SaveImplementation
{
    /**
     * @param Request $request
     * @param QueryParameters $parameters
     * @return void
     * @throws HttpResponseException
     */
    public function creating(Request $request, QueryParameters $parameters): void;

    /**
     * @param object $model
     * @param Request $request
     * @param QueryParameters $parameters
     * @return void
     * @throws HttpResponseException
     */
    public function created(object $model, Request $request, QueryParameters $parameters): void;
}
