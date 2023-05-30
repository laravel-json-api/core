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

namespace LaravelJsonApi\Core\Extensions\Atomic\Parsers;

use Closure;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceObjectParser;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Operation;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Store;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;

class StoreParser implements ParsesOperationFromArray
{
    /**
     * StoreParser constructor
     *
     * @param ResourceObjectParser $resourceParser
     */
    public function __construct(private readonly ResourceObjectParser $resourceParser)
    {
    }

    /**
     * @inheritDoc
     */
    public function parse(array $operation, Closure $next): Operation
    {
        if ($this->isStore($operation)) {
            return new Store(
                new Href($operation['href']),
                $this->resourceParser->parse($operation['data']),
                $operation['meta'] ?? [],
            );
        }

        return $next($operation);
    }

    /**
     * @param array $operation
     * @return bool
     */
    private function isStore(array $operation): bool
    {
        return $operation['op'] === OpCodeEnum::Add->value &&
            !empty($operation['href'] ?? null) &&
            (is_array($operation['data'] ?? null) && isset($operation['data']['type']));
    }
}
