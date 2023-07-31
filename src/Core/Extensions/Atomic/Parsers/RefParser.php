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

use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;

class RefParser
{
    /**
     * Parse a ref from an array.
     *
     * @param array $ref
     * @return Ref
     */
    public function parse(array $ref): Ref
    {
        return new Ref(
            ResourceType::cast($ref['type']),
            ResourceId::nullable($ref['id'] ?? null),
            ResourceId::nullable($ref['lid'] ?? null),
            $ref['relationship'] ?? null,
        );
    }

    /**
     * Parse a ref, if one is provided.
     *
     * @param array|null $ref
     * @return Ref|null
     */
    public function nullable(?array $ref): ?Ref
    {
        if ($ref !== null) {
            return $this->parse($ref);
        }

        return null;
    }
}