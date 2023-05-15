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

namespace LaravelJsonApi\Core\Extensions\Atomic;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use LaravelJsonApi\Core\Document\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Support\Contracts;

class Ref implements JsonSerializable, Arrayable
{
    /**
     * @param ResourceIdentifier $identifier
     * @param string|null $relationship
     */
    public function __construct(
        public readonly ResourceIdentifier $identifier,
        public readonly ?string $relationship = null,
    ) {
        Contracts::assert(
            $this->relationship === null || !empty(trim($this->relationship)),
            'Relationship must be a non-empty string if provided.',
        );
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $arr = $this->identifier->toArray();

        if ($this->relationship) {
            $arr['relationship'] = $this->relationship;
        }

        return $arr;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $json = $this->identifier->jsonSerialize();

        if ($this->relationship) {
            $json['relationship'] = $this->relationship;
        }

        return $json;
    }
}