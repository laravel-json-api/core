<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Document\ResourceObject;

use LaravelJsonApi\Core\Contracts\Document\RelationshipObject;
use LaravelJsonApi\Core\Contracts\Document\ResourceObject;
use LaravelJsonApi\Core\Document\Concerns\HasLinks;
use LaravelJsonApi\Core\Document\Concerns\HasMeta;

class ToMany implements RelationshipObject
{

    use HasLinks;
    use HasMeta;

    /**
     * @var iterable
     */
    private $resources;

    /**
     * @var bool
     */
    private $showData;

    /**
     * ToMany constructor.
     *
     * @param iterable $resources
     * @param bool $showData
     */
    public function __construct(iterable $resources, bool $showData = true)
    {
        $this->resources = $resources;
        $this->showData = $showData;
    }

    /**
     * @inheritDoc
     */
    public function data()
    {
        /** @var ResourceObject $resource */
        foreach ($this->resources as $resource) {
            yield $resource->identifier();
        }
    }

    /**
     * @inheritDoc
     */
    public function showData(): bool
    {
        return $this->showData;
    }

    /**
     * @inheritDoc
     */
    public function related()
    {
        yield from $this->resources;
    }

}
