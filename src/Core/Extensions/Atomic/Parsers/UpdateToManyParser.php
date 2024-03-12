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
