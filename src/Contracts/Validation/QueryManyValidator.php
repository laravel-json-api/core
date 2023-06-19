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

namespace LaravelJsonApi\Contracts\Validation;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;

interface QueryManyValidator
{
    /**
     * Make a validate for query parameters in the provided request.
     *
     * @param Request $request
     * @return Validator
     */
    public function forRequest(Request $request): Validator;

    /**
     * Make a validator for query parameters when fetching zero-to-many resources.
     *
     * @param Request|null $request
     * @param array $parameters
     * @return Validator
     */
    public function make(?Request $request, array $parameters): Validator;
}