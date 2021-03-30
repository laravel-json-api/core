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
    public function withPageKey(string $key): self
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
    public function withPerPageKey(string $key): self
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
    public function withDefaultPerPage(?int $perPage): self
    {
        $this->defaultPerPage = $perPage;

        return $this;
    }

}
