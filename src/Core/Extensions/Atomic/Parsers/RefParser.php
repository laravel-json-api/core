<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Extensions\Atomic\Parsers;

use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

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
