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
