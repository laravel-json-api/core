<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Document\Input\Parsers;

use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class ResourceObjectParser
{
    /**
     * Parse a resource object from an array.
     *
     * @param array $data
     * @return ResourceObject
     */
    public function parse(array $data): ResourceObject
    {
        assert(isset($data['type']), 'Resource object array must contain a type.');

        return new ResourceObject(
            type: ResourceType::cast($data['type']),
            id: ResourceId::nullable($data['id'] ?? null),
            lid: ResourceId::nullable($data['lid'] ?? null),
            attributes: $data['attributes'] ?? [],
            relationships: $data['relationships'] ?? [],
            meta: $data['meta'] ?? [],
        );
    }
}
