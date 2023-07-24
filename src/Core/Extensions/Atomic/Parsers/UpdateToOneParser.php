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

use LaravelJsonApi\Core\Document\Input\Parsers\ResourceIdentifierParser;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToOne;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;

class UpdateToOneParser implements ParsesOperationFromArray
{
    /**
     * UpdateToOneParser constructor
     *
     * @param HrefOrRefParser $targetParser
     * @param ResourceIdentifierParser $identifierParser
     */
    public function __construct(
        private readonly HrefOrRefParser $targetParser,
        private readonly ResourceIdentifierParser $identifierParser
    ) {
    }

    /**
     * @inheritDoc
     */
    public function parse(array $operation): ?UpdateToOne
    {
        if ($this->isUpdateToOne($operation)) {
            return new UpdateToOne(
                $this->targetParser->parse($operation),
                $this->identifierParser->nullable($operation['data']),
                $operation['meta'] ?? [],
            );
        }

        return null;
    }

    /**
     * @param array $operation
     * @return bool
     */
    private function isUpdateToOne(array $operation): bool
    {
        if ($operation['op'] !== OpCodeEnum::Update->value) {
            return false;
        }

        if (!array_key_exists('data', $operation)) {
            return false;
        }

        $hasTarget = false;

        if (isset($operation['ref']) && isset($operation['ref']['relationship'])) {
            $hasTarget = true;
        } else if (isset($operation['href']) && Href::make($operation['href'])->hasRelationshipName()) {
            $hasTarget = true;
        }

        return $hasTarget && $this->isIdentifier($operation['data']);
    }

    /**
     * @param array|null $data
     * @return bool
     */
    private function isIdentifier(?array $data): bool
    {
        if ($data === null) {
            return true;
        }

        return isset($data['type']) &&
            (isset($data['id']) || isset($data['lid'])) &&
            !isset($data['attributes']) &&
            !isset($data['relationships']);
    }
}
