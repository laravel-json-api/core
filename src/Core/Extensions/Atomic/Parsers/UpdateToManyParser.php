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

use LaravelJsonApi\Core\Document\Input\Parsers\ListOfResourceIdentifiersParser;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Operation;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;

class UpdateToManyParser implements ParsesOperationFromArray
{
    /**
     * UpdateToManyParser constructor
     *
     * @param HrefOrRefParser $targetParser
     * @param ListOfResourceIdentifiersParser $identifiersParser
     */
    public function __construct(
        private readonly HrefOrRefParser $targetParser,
        private readonly ListOfResourceIdentifiersParser $identifiersParser,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function parse(array $operation): ?Operation
    {
        if ($this->isUpdateToMany($operation)) {
            return new UpdateToMany(
                OpCodeEnum::from($operation['op']),
                $this->targetParser->parse($operation),
                $this->identifiersParser->parse($operation['data']),
                $operation['meta'] ?? [],
            );
        }

        return null;
    }

    /**
     * @param array $operation
     * @return bool
     */
    private function isUpdateToMany(array $operation): bool
    {
        $data = $operation['data'] ?? null;

        if (!is_array($data) || !array_is_list($data)) {
            return false;
        }

        return $this->targetParser->hasRelationship($operation);
    }
}
