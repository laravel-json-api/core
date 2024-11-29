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

trait HasHeaders
{
    /**
     * @var array
     */
    public array $headers = [];

    /**
     * Set a header.
     *
     * @param string $name
     * @param string|null $value
     * @return $this
     */
    public function withHeader(string $name, ?string $value = null): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Set response headers.
     *
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers): static
    {
        $this->headers = [...$this->headers, ...$headers];

        return $this;
    }

    /**
     * Remove response headers.
     *
     * @param string ...$headers
     * @return $this
     */
    public function withoutHeaders(string ...$headers): static
    {
        foreach ($headers as $header) {
            unset($this->headers[$header]);
        }

        return $this;
    }
}
