<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

use Closure;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Resources\JsonApiRelation;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Support\Str;
use LogicException;

class Relation implements JsonApiRelation
{

    /**
     * @var object
     */
    private object $resource;

    /**
     * @var string
     */
    private string $baseUri;

    /**
     * @var string
     */
    private string $fieldName;

    /**
     * @var string|null
     */
    private ?string $keyName;

    /**
     * @var string|null
     */
    private ?string $uriName = null;

    /**
     * @var mixed|null
     */
    private $data;

    /**
     * @var bool
     */
    private bool $hasData = false;

    /**
     * @var bool
     */
    private bool $showData = false;

    /**
     * @var bool
     */
    private bool $showSelf = true;

    /**
     * @var bool
     */
    private bool $showRelated = true;

    /**
     * @var bool
     */
    private bool $validated = false;

    /**
     * @var array|Closure
     */
    private $meta;

    /**
     * Relation constructor.
     *
     * @param object $resource
     * @param string $baseUri
     * @param string $fieldName
     * @param string|null $keyName
     */
    public function __construct(
        object $resource,
        string $baseUri,
        string $fieldName,
        string $keyName = null
    ) {
        $this->resource = $resource;
        $this->baseUri = $baseUri;
        $this->fieldName = $fieldName;
        $this->keyName = $keyName ?: Str::camel($fieldName);
    }

    /**
     * @inheritDoc
     */
    public function fieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * @inheritDoc
     */
    public function links(): Links
    {
        $links = new Links();

        if ($this->showSelf) {
            $links->push(new Link('self', $this->selfUrl()));
        }

        if ($this->showRelated) {
            $links->push(new Link('related', $this->relatedUrl()));
        }

        return $links;
    }

    /**
     * @inheritDoc
     */
    public function meta(): ?array
    {
        if ($this->meta instanceof Closure) {
            $this->meta = ($this->meta)();
        }

        return $this->meta;
    }

    /**
     * @inheritDoc
     */
    public function data()
    {
        if (false === $this->hasData) {
            return $this->resource->{$this->keyName};
        }

        if ($this->data instanceof Closure) {
            return ($this->data)();
        }

        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function showData(): bool
    {
        return $this->showData;
    }

    /**
     * @return string
     */
    public function selfUrl(): string
    {
        return \sprintf(
            '%s/relationships/%s',
            $this->baseUri,
            $this->uriFieldName()
        );
    }

    /**
     * @return string
     */
    public function relatedUrl(): string
    {
        return \sprintf(
            '%s/%s',
            $this->baseUri,
            $this->uriFieldName()
        );
    }

    /**
     * Use the field-name as-is for relationship URLs.
     *
     * @return JsonApiRelation
     */
    public function retainFieldName(): JsonApiRelation
    {
        $this->uriName = $this->fieldName();

        return $this;
    }

    /**
     * Use the provided string as the URI fragment for the field name.
     *
     * @param string $uri
     * @return JsonApiRelation
     */
    public function withUriFieldName(string $uri): JsonApiRelation
    {
        if (empty($uri)) {
            throw new InvalidArgumentException('Expecting a non-empty string URI fragment.');
        }

        $this->uriName = $uri;

        return $this;
    }

    /**
     * @return JsonApiRelation
     */
    public function withoutSelfLink(): JsonApiRelation
    {
        $this->showSelf = false;

        return $this;
    }

    /**
     * @return JsonApiRelation
     */
    public function withoutRelatedLink(): JsonApiRelation
    {
        $this->showRelated = false;

        return $this;
    }

    /**
     * @return JsonApiRelation
     */
    public function withoutLinks(): JsonApiRelation
    {
        $this->withoutSelfLink();
        $this->withoutRelatedLink();

        return $this;
    }

    /**
     * @param mixed $data
     * @return JsonApiRelation
     */
    public function withData($data): JsonApiRelation
    {
        $this->data = $data;
        $this->hasData = true;

        return $this;
    }

    /**
     * Always show the data member of the relation.
     *
     * @return JsonApiRelation
     */
    public function alwaysShowData(): JsonApiRelation
    {
        $this->showData = true;

        return $this;
    }

    /**
     * Always show the data member of the relation if it is loaded on the model.
     *
     * @return JsonApiRelation
     */
    public function showDataIfLoaded(): JsonApiRelation
    {
        if (method_exists($this->resource, 'relationLoaded')) {
            $this->showData = $this->resource->relationLoaded($this->keyName);
            return $this;
        }

        throw new LogicException('Expecting resource to have a relationLoaded method.');
    }

    /**
     * @param $meta
     * @return JsonApiRelation
     */
    public function withMeta($meta): JsonApiRelation
    {
        if (!is_array($meta) && !$meta instanceof Closure) {
            throw new InvalidArgumentException('Expecting meta to be an array or a closure.');
        }

        $this->meta = $meta;

        return $this;
    }

    /**
     * Mark the relation as required for validation.
     *
     * @return JsonApiRelation
     */
    public function mustValidate(): JsonApiRelation
    {
        $this->validated = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->validated;
    }

    /**
     * Get the field name for URIs.
     *
     * @return string
     */
    private function uriFieldName(): string
    {
        if ($this->uriName) {
            return $this->uriName;
        }

        return $this->uriName = Str::dasherize($this->fieldName);
    }

}
