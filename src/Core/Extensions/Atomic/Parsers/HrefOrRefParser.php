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

use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;

class HrefOrRefParser
{
    /**
     * HrefOrRefParser constructor
     *
     * @param HrefParser $hrefParser
     * @param RefParser $refParser
     */
    public function __construct(
        private readonly HrefParser $hrefParser,
        private readonly RefParser $refParser
    ) {
    }

    /**
     * Parse an href or ref from the operation.
     *
     * @param array $operation
     * @return ParsedHref|Ref
     */
    public function parse(array $operation): ParsedHref|Ref
    {
        assert(
            isset($operation['href']) || isset($operation['ref']),
            'Expecting operation to have a target (href or ref).',
        );

        if (isset($operation['href'])) {
            return $this->hrefParser->parse($operation['href']);
        }

        return $this->refParser->parse($operation['ref']);
    }

    /**
     * Parse an href or ref from the operation, if there is one.
     *
     * @param array $operation
     * @return ParsedHref|Ref|null
     */
    public function nullable(array $operation): ParsedHref|Ref|null
    {
        if (isset($operation['href']) || isset($operation['ref'])) {
            return $this->parse($operation);
        }

        return null;
    }

    /**
     * If parsed, will the operation target a relationship via the ref or href?
     *
     * @param array $operation
     * @return bool
     */
    public function hasRelationship(array $operation): bool
    {
        if (isset($operation['ref']['relationship'])) {
            return true;
        }

        if (isset($operation['href'])) {
            return $this->hrefParser->hasRelationship($operation['href']);
        }

        return false;
    }
}
