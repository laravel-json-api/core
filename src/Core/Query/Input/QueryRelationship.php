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

namespace LaravelJsonApi\Core\Query\Input;

use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class QueryRelationship extends Query
{
    /**
     * QueryRelationship constructor
     *
     * @param ResourceType $type
     * @param ResourceId $id
     * @param string $fieldName
     * @param array $parameters
     */
    public function __construct(
        ResourceType $type,
        public readonly ResourceId $id,
        public readonly string $fieldName,
        array $parameters = [],
    ) {
        parent::__construct(QueryCodeEnum::Relationship, $type, $parameters);
    }

    /**
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }
}
