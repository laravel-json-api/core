<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Resources\Concerns;

use Closure;
use LaravelJsonApi\Core\Resources\ConditionalField;
use LaravelJsonApi\Core\Resources\ConditionalFields;
use function boolval;

trait ConditionallyLoadsFields
{

    /**
     * Conditionally include a field value.
     *
     * @param bool|mixed $check
     * @param mixed $value
     * @return ConditionalField
     */
    protected function when($check, $value): ConditionalField
    {
        return new ConditionalField(boolval($check), $value);
    }

    /**
     * Conditionally include a set of field values.
     *
     * @param bool|mixed $check
     * @param Closure|iterable $values
     * @return ConditionalFields
     */
    protected function mergeWhen($check, $values): ConditionalFields
    {
        return new ConditionalFields(boolval($check), $values);
    }
}
