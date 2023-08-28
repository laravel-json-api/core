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

namespace LaravelJsonApi\Core\Extensions\Atomic\Operations;

use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;

class UpdateToMany extends Operation
{
    /**
     * UpdateToMany constructor
     *
     * @param OpCodeEnum $op
     * @param Ref|Href $target
     * @param ListOfResourceIdentifiers $data
     * @param array $meta
     */
    public function __construct(
        OpCodeEnum $op,
        Ref|Href $target,
        public readonly ListOfResourceIdentifiers $data,
        array $meta = []
    ) {
        parent::__construct(
            op: $op,
            target: $target,
            meta: $meta,
        );
    }

    /**
     * @return string
     */
    public function getFieldName(): string
    {
        $name = parent::getFieldName();

        assert(!empty($name), 'Expecting a field name to be set.');

        return $name;
    }

    /**
     * @return bool
     */
    public function isAttachingRelationship(): bool
    {
        return OpCodeEnum::Add === $this->op;
    }

    /**
     * @return bool
     */
    public function isUpdatingRelationship(): bool
    {
        return OpCodeEnum::Update === $this->op;
    }

    /**
     * @return bool
     */
    public function isDetachingRelationship(): bool
    {
        return OpCodeEnum::Remove === $this->op;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return array_filter([
            'op' => $this->op->value,
            'href' => $this->href()?->value,
            'ref' => $this->ref()?->toArray(),
            'data' => $this->data->toArray(),
            'meta' => empty($this->meta) ? null : $this->meta,
        ], static fn (mixed $value) => $value !== null);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'op' => $this->op,
            'href' => $this->href(),
            'ref' => $this->ref(),
            'data' => $this->data,
            'meta' => empty($this->meta) ? null : $this->meta,
        ], static fn (mixed $value) => $value !== null);
    }
}
