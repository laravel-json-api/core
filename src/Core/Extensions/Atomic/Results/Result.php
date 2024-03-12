<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Extensions\Atomic\Results;

use LaravelJsonApi\Core\Support\Contracts;

class Result
{
    /**
     * @param array $meta
     * @return self
     */
    public static function none(array $meta = []): self
    {
        return new self(null, false, $meta);
    }

    /**
     * Result constructor
     *
     * @param mixed $data
     * @param bool $hasData
     * @param array $meta
     */
    public function __construct(
        public readonly mixed $data,
        public readonly bool $hasData,
        public readonly array $meta = [],
    ) {
        Contracts::assert(
            $this->hasData || $this->data === null,
            'Result data must be null when result has no data.',
        );
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->hasData && empty($this->meta);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }
}
