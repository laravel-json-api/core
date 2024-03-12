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

class ResourceIdentifierOrListOfIdentifiersParser
{
    /**
     * ResourceIdentifierOrListOfIdentifiersParser constructor
     *
     * @param ResourceIdentifierParser $identifierParser
     * @param ListOfResourceIdentifiersParser $listParser
     */
    public function __construct(
        private readonly ResourceIdentifierParser $identifierParser,
        private readonly ListOfResourceIdentifiersParser $listParser,
    ) {
    }

    /**
     * Parse data to an identifier or list of identifiers.
     *
     * @param array $data
     * @return ResourceIdentifier|ListOfResourceIdentifiers
     */
    public function parse(array $data): ResourceIdentifier|ListOfResourceIdentifiers
    {
        if (array_is_list($data)) {
            return $this->listParser->parse($data);
        }

        return $this->identifierParser->parse($data);
    }

    /**
     * @param array|null $data
     * @return ResourceIdentifier|ListOfResourceIdentifiers|null
     */
    public function nullable(?array $data): ResourceIdentifier|ListOfResourceIdentifiers|null
    {
        if ($data === null) {
            return null;
        }

        return $this->parse($data);
    }
}
