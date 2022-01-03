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

namespace LaravelJsonApi\Core\Document;

use InvalidArgumentException;
use LaravelJsonApi\Contracts\Serializable;
use LaravelJsonApi\Core\Json\Json;

class Link implements Serializable
{

    use Concerns\HasMeta;
    use Concerns\Serializable;

    /**
     * @var string
     */
    private string $key;

    /**
     * @var LinkHref
     */
    private LinkHref $href;

    /**
     * @param string $key
     * @param array|string $value
     * @return Link
     */
    public static function fromArray(string $key, $value): self
    {
        if (is_array($value) && isset($value['href'])) {
            return new self(
                $key,
                $value['href'],
                Json::hash($value['meta'] ?? [])
            );
        }

        return new self($key, $value);
    }

    /**
     * Link constructor.
     *
     * @param string $key
     * @param LinkHref|string $href
     * @param mixed|null $meta
     */
    public function __construct(string $key, $href, $meta = null)
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Expecting key to be a non-empty string.');
        }

        $this->key = $key;
        $this->href = LinkHref::cast($href);
        $this->setMeta($meta);
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return LinkHref
     */
    public function href(): LinkHref
    {
        return $this->href;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $arr = ['href' => $this->href->toString()];

        if ($this->hasMeta()) {
            $arr['meta'] = $this->meta->toArray();
        }

        return $arr;
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        if ($this->doesntHaveMeta()) {
            return $this->href;
        }

        return [
            'href' => $this->href,
            'meta' => $this->meta,
        ];
    }

}
