<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Schema;

interface ID extends Field
{

    /**
     * Get the model key for the id.
     *
     * @return string|null
     */
    public function key(): ?string;

    /**
     * Get the regex pattern for the ID field.
     *
     * @return string
     */
    public function pattern(): string;

    /**
     * Does the value match the pattern?
     *
     * If a delimiter is provided, the value can hold one-to-many ids separated
     * by the provided delimiter.
     *
     * @param string $value
     * @param string $delimiter
     * @return bool
     */
    public function match(string $value, string $delimiter = ''): bool;

    /**
     * Do all the values match the pattern?
     *
     * @param array<string> $values
     * @return bool
     */
    public function matchAll(array $values): bool;

    /**
     * Does the resource accept client generated ids?
     *
     * @return bool
     */
    public function acceptsClientIds(): bool;

    /**
     * Is the resource sortable by its id?
     *
     * @return bool
     */
    public function isSortable(): bool;
}
