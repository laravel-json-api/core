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

namespace LaravelJsonApi\Core\Responses\Concerns;

use LaravelJsonApi\Contracts\Resources\JsonApiRelation;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Json\Hash;
use LaravelJsonApi\Core\Resources\JsonApiResource;
use LogicException;

trait HasRelationship
{
    use HasRelationshipLinks;
    use HasRelationshipMeta;

    /**
     * @var JsonApiResource
     */
    private JsonApiResource $resource;

    /**
     * @var string
     */
    private string $fieldName;

    /**
     * Get all meta (relationship meta and provided meta).
     *
     * @return Hash|null
     */
    private function allMeta(): ?Hash
    {
        return Hash::cast($this->metaForRelationship())
            ->merge($this->meta());
    }

    /**
     * Get the relationship meta.
     *
     * @return array|null
     */
    private function metaForRelationship(): ?array
    {
        if ($this->hasRelationMeta && $relation = $this->relation()) {
            return $relation->meta();
        }

        return null;
    }

    /**
     * Get all links (relationship links and provided links).
     *
     * @return Links
     */
    private function allLinks(): Links
    {
        return $this
            ->linksForRelationship()
            ->merge($this->links());
    }

    /**
     * Get the relationship links.
     *
     * @return Links|null
     */
    private function linksForRelationship(): Links
    {
        if ($this->hasRelationLinks && $relation = $this->relation()) {
            return $relation->links();
        }

        return new Links();
    }

    /**
     * @return JsonApiRelation|null
     */
    private function relation(): ?JsonApiRelation
    {
        try {
            return $this->resource->relationship($this->fieldName);
        } catch (LogicException $ex) {
            return null;
        }
    }
}
