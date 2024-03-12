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

use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;

class ListOfResourceIdentifiersParser
{
    /**
     * ListOfResourceIdentifiersParser constructor
     *
     * @param ResourceIdentifierParser $identifierParser
     */
    public function __construct(private readonly ResourceIdentifierParser $identifierParser)
    {
    }

    /**
     * Parse a list of resource identifiers.
     *
     * @param array $data
     * @return ListOfResourceIdentifiers
     */
    public function parse(array $data): ListOfResourceIdentifiers
    {
        $identifiers = array_map(
            fn (array $identifier): ResourceIdentifier => $this->identifierParser->parse($identifier),
            $data,
        );

        return new ListOfResourceIdentifiers(...$identifiers);
    }
}
