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
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;

interface DestroyValidator
{
    /**
     * Extract validation data for a destroy operation.
     *
     * @param object $model
     * @param Delete $operation
     * @return array
     */
    public function extract(object $model, Delete $operation): array;

    /**
     * Make a validator for the destroy operation.
     *
     * @param Request|null $request
     * @param object $model
     * @param Delete $operation
     * @return Validator
     */
    public function make(?Request $request, object $model, Delete $operation): Validator;
}