<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Responses\Concerns;

use LaravelJsonApi\Core\Document\JsonApi;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Json\Hash;
use LaravelJsonApi\Core\JsonApiService;
use LaravelJsonApi\Core\Server\Concerns\ServerAware;
use function array_merge;

trait IsResponsable
{
    use ServerAware;
    use HasHeaders;

    /**
     * @var JsonApi|null
     */
    public ?JsonApi $jsonApi = null;

    /**
     * @var Hash|null
     */
    public ?Hash $meta = null;

    /**
     * @var Links|null
     */
    public ?Links $links = null;

    /**
     * @var int
     */
    public int $encodeOptions = 0;

    /**
     * Add the top-level JSON:API member to the response.
     *
     * @param $jsonApi
     * @return $this
     */
    public function withJsonApi($jsonApi): static
    {
        $this->jsonApi = JsonApi::nullable($jsonApi);

        return $this;
    }

    /**
     * @return JsonApi
     */
    public function jsonApi(): JsonApi
    {
        if ($this->jsonApi) {
            return $this->jsonApi;
        }

        return $this->jsonApi = new JsonApi();
    }

    /**
     * Add top-level meta to the response.
     *
     * @param $meta
     * @return $this
     */
    public function withMeta($meta): static
    {
        $this->meta = Hash::cast($meta);

        return $this;
    }

    /**
     * @return Hash
     */
    public function meta(): Hash
    {
        if ($this->meta) {
            return $this->meta;
        }

        return $this->meta = new Hash();
    }

    /**
     * Add top-level links to the response.
     *
     * @param $links
     * @return $this
     */
    public function withLinks($links): static
    {
        $this->links = Links::cast($links);

        return $this;
    }

    /**
     * @return Links
     */
    public function links(): Links
    {
        if ($this->links) {
            return $this->links;
        }

        return $this->links = new Links();
    }

    /**
     * Set JSON encode options.
     *
     * @param int $options
     * @return $this
     */
    public function withEncodeOptions(int $options): static
    {
        $this->encodeOptions = $options;

        return $this;
    }

    /**
     * @return array
     */
    protected function headers(): array
    {
        return array_merge(
            ['Content-Type' => 'application/vnd.api+json'],
            $this->headers,
        );
    }

    /**
     * Set the default top-level JSON:API member.
     *
     * @return void
     */
    protected function defaultJsonApi(): void
    {
        if ($this->jsonApi()->isEmpty()) {
            $jsonApi = new JsonApi(JsonApiService::JSON_API_VERSION);

            if ($server = $this->serverIfExists()) {
                $jsonApi = $server->jsonApi();
            }

            $this->withJsonApi($jsonApi);
        }
    }
}
