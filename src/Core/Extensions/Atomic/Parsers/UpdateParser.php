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

use LaravelJsonApi\Core\Document\Input\Parsers\ResourceObjectParser;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;

class UpdateParser implements ParsesOperationFromArray
{
    /**
     * UpdateParser constructor
     *
     * @param HrefOrRefParser $targetParser
     * @param ResourceObjectParser $resourceParser
     */
    public function __construct(
        private readonly HrefOrRefParser $targetParser,
        private readonly ResourceObjectParser $resourceParser
    ) {
    }

    /**
     * @inheritDoc
     */
    public function parse(array $operation): ?Update
    {
        if ($this->isUpdate($operation)) {
            return new Update(
                $this->targetParser->nullable($operation),
                $this->resourceParser->parse($operation['data']),
                $operation['meta'] ?? [],
            );
        }

        return null;
    }

    /**
     * @param array $operation
     * @return bool
     */
    private function isUpdate(array $operation): bool
    {
        if ($operation['op'] !== OpCodeEnum::Update->value) {
            return false;
        }

        if ($this->targetParser->hasRelationship($operation)) {
            return false;
        }

        return is_array($operation['data'] ?? null) && isset($operation['data']['type']);
    }
}
