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

namespace LaravelJsonApi\Core\Document\Input\Parsers;

use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class ResourceIdentifierParser
{
    /**
     * Parse the data to a resource identifier.
     *
     * @param array $data
     * @return ResourceIdentifier
     */
    public function parse(array $data): ResourceIdentifier
    {
        assert(isset($data['type']), 'Resource identifier array must contain a type.');

        return new ResourceIdentifier(
            type: new ResourceType($data['type']),
            id: ResourceId::nullable($data['id'] ?? null),
            lid: ResourceId::nullable($data['lid'] ?? null),
            meta: $data['meta'] ?? [],
        );
    }

    /**
     * Parse data to an identifier, if it is not null.
     *
     * @param array|null $data
     * @return ResourceIdentifier|null
     */
    public function nullable(?array $data): ?ResourceIdentifier
    {
        if ($data === null) {
            return null;
        }

        return $this->parse($data);
    }
}
