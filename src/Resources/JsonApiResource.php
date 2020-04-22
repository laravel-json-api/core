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

namespace LaravelJsonApi\Core\Resources;

use ArrayAccess;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Resources\DelegatesToResource;
use LaravelJsonApi\Core\Contracts\Document\RelationshipObject;
use LaravelJsonApi\Core\Contracts\Document\ResourceIdentifierObject;
use LaravelJsonApi\Core\Contracts\Document\ResourceObject;
use LaravelJsonApi\Core\Document\Concerns\ConditionallyLoadsAttributes;
use LaravelJsonApi\Core\Document\Concerns\HasMeta;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\LinkHref;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Document\ResourceObject\Identifier;
use LogicException;
use function sprintf;

abstract class JsonApiResource implements ResourceObject, ArrayAccess, UrlRoutable
{

    use ConditionallyLoadsAttributes;
    use DelegatesToResource;
    use HasMeta;

    /**
     * @var mixed
     */
    protected $resource;

    /**
     * @var Links|null
     */
    protected $links;

    /**
     * Get the URL for the resource's self link.
     *
     * @return string
     */
    abstract protected function selfUrl(): string;

    /**
     * JsonApiResource constructor.
     *
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return (string) $this->getRouteKey();
    }

    /**
     * @inheritDoc
     */
    public function identifier(): ResourceIdentifierObject
    {
        return new Identifier($this->type(), $this->id());
    }

    /**
     * @inheritDoc
     */
    public function links(): Links
    {
        if ($this->links) {
            return $this->links;
        }

        return $this->links = Links::cast(
            $this->selfLink()
        );
    }

    /**
     * @inheritDoc
     */
    public function hasLinks(): bool
    {
        return $this->links()->isNotEmpty();
    }

    /**
     * @inheritDoc
     */
    public function relation(string $fieldName): RelationshipObject
    {
        if ($relation = $this->relationships()[$fieldName] ?? null) {
            return $relation;
        }

        throw new LogicException(sprintf(
            'Resource %s does not have a relationship called %s.',
            $this->type(),
            $fieldName
        ));
    }

    /**
     * @return Link
     */
    protected function selfLink(): Link
    {
        return new Link('self', new LinkHref($this->selfUrl()));
    }

}
