<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Contracts\Schema;

interface Field
{
    /**
     * Get the field name.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Is the field mass assignable?
     *
     * @return bool
     */
    public function isFillable(): bool;

    /**
     * Is the field guarded from mass assignment?
     *
     * @return bool
     */
    public function isGuarded(): bool;

    /**
     * Is the field allowed in sparse field sets?
     *
     * @return bool
     */
    public function isSparseField(): bool;

    /**
     * Is the field sortable?
     *
     * @return bool
     */
    public function isSortable(): bool;

    /**
     * Is the field filterable?
     *
     * @return bool
     */
    public function isFilter(): bool;

}
