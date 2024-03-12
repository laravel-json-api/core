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
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;

class CreateParser implements ParsesOperationFromArray
{
    /**
     * CreateParser constructor
     *
     * @param HrefParser $hrefParser
     * @param ResourceObjectParser $resourceParser
     */
    public function __construct(
        private readonly HrefParser $hrefParser,
        private readonly ResourceObjectParser $resourceParser,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function parse(array $operation): ?Create
    {
        if ($this->isStore($operation)) {
            return new Create(
                $this->hrefParser->nullable($operation['href'] ?? null),
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
    private function isStore(array $operation): bool
    {
        return $operation['op'] === OpCodeEnum::Add->value &&
            (!isset($operation['ref'])) &&
            (is_array($operation['data'] ?? null) && isset($operation['data']['type']));
    }
}
