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

use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;

class DeleteParser implements ParsesOperationFromArray
{
    /**
     * DeleteParser constructor
     *
     * @param HrefOrRefParser $targetParser
     */
    public function __construct(private readonly HrefOrRefParser $targetParser)
    {
    }

    /**
     * @inheritDoc
     */
    public function parse(array $operation): ?Delete
    {
        if ($this->isDelete($operation)) {
            return new Delete(
                $this->targetParser->parse($operation),
                $operation['meta'] ?? [],
            );
        }

        return null;
    }

    /**
     * @param array $operation
     * @return bool
     */
    private function isDelete(array $operation): bool
    {
        if ($operation['op'] !== OpCodeEnum::Remove->value) {
            return false;
        }

        if (!isset($operation['ref']) && !isset($operation['href'])) {
            return false;
        }

        return !$this->targetParser->hasRelationship($operation);
    }
}
