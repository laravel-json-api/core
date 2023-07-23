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

use LaravelJsonApi\Core\Document\Input\Parsers\ResourceObjectParser;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;

class CreateParser implements ParsesOperationFromArray
{
    /**
     * CreateParser constructor
     *
     * @param ResourceObjectParser $resourceParser
     */
    public function __construct(private readonly ResourceObjectParser $resourceParser)
    {
    }

    /**
     * @inheritDoc
     */
    public function parse(array $operation): ?Create
    {
        if ($this->isStore($operation)) {
            $href = $operation['href'] ?? null;
            return new Create(
                $href ? new Href($operation['href']) : null,
                $this->resourceParser->parse($operation['data']),
                $operation['meta'] ?? [],
            );
        }

        return null;
    }

    /**
     * @param array $operation
     * @return bool
     */
    private function isStore(array $operation): bool
    {
        return $operation['op'] === OpCodeEnum::Add->value &&
            (!isset($operation['ref'])) &&
            (is_array($operation['data'] ?? null) && isset($operation['data']['type']));
    }
}
