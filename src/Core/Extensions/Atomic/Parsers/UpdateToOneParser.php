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

use LaravelJsonApi\Core\Document\Input\Parsers\ResourceIdentifierParser;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToOne;
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

        return $this->targetParser->hasRelationship($operation) &&
            $this->isIdentifier($operation['data']);
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
