<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Pagination\Concerns;

trait HasPageNumbers
{

    /**
     * @var string
     */
    private string $pageKey = 'number';

    /**
     * @var string
     */
    private string $perPageKey = 'size';

    /**
     * @var int|null
     */
    private ?int $defaultPerPage = null;

    /**
     * @var int
     */
    private int $maxPerPage = 0;

    /**
     * @var bool
     */
    private bool $required = false;

    /**
     * Get the keys expected in the `page` query parameter for this paginator.
     *
     * @return array
     */
    public function keys(): array
    {
        return [
            $this->pageKey,
            $this->perPageKey,
        ];
    }

    /**
     * Set the key name for the page number.
     *
     * @param string $key
     * @return $this
     */
    public function withPageKey(string $key): static
    {
        $this->pageKey = $key;

        return $this;
    }

    /**
     * Set the key name for the per-page amount.
     *
     * @param string $key
     * @return $this
     */
    public function withPerPageKey(string $key): static
    {
        $this->perPageKey = $key;

        return $this;
    }

    /**
     * Use the provided number as the default items per-page.
     *
     * @param int|null $perPage
     * @return $this
     */
    public function withDefaultPerPage(?int $perPage): static
    {
        $this->defaultPerPage = $perPage;

        return $this;
    }

    /**
     * Set the maximum number of records per-page.
     *
     * @param int $max
     * @return $this
     */
    public function withMaxPerPage(int $max): static
    {
        assert($max > 0, 'Expecting max per page to be greater than zero.');

        $this->maxPerPage = $max;

        return $this;
    }

    /**
     * Force the client to always provided a page number.
     *
     * @return $this
     */
    public function required(): static
    {
        $this->required = true;

        return $this;
    }
}
