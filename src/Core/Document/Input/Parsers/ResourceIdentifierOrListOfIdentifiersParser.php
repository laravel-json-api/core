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
