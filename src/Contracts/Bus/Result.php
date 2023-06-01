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

namespace LaravelJsonApi\Contracts\Bus;

use LaravelJsonApi\Core\Document\ErrorList;

interface Result
{
    /**
     * Is this a success result?
     *
     * @return bool
     */
    public function didSucceed(): bool;

    /**
     * Is this a failure result?
     *
     * @return bool
     */
    public function didFail(): bool;

    /**
     * Get the result errors.
     *
     * @return ErrorList
     */
    public function errors(): ErrorList;
}
