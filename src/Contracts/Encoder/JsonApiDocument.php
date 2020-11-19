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

namespace LaravelJsonApi\Contracts\Encoder;

use LaravelJsonApi\Contracts\Serializable;
use LaravelJsonApi\Core\Document\JsonApi;

interface JsonApiDocument extends Serializable
{

    /**
     * Set the top-level JSON API member.
     *
     * Encoders MUST only use the provided JSON API object
     * if it is non-empty. Otherwise encoders should fall back
     * to the default JSON API object, which is provided by the
     * server that is doing the encoding.
     *
     * @param JsonApi|array|string|null $jsonApi
     * @return $this
     */
    public function withJsonApi($jsonApi): self;

    /**
     * Forget the top-level JSON API member.
     *
     * @return $this
     */
    public function withoutJsonApi(): self;

    /**
     * Set the top-level links member.
     *
     * @param $links
     * @return $this
     */
    public function withLinks($links): self;

    /**
     * Forget top-level links.
     *
     * @return $this
     */
    public function withoutLinks(): self;

    /**
     * Set the top-level meta member.
     *
     * @param $meta
     * @return $this
     */
    public function withMeta($meta): self;

    /**
     * Forget top-level meta.
     *
     * @return $this
     */
    public function withoutMeta(): self;

}
