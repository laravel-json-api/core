<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Schema\Concerns;

trait MatchesIds
{
    /**
     * @var string
     */
    private string $pattern = '[0-9]+';

    /**
     * @var string
     */
    private string $flags = 'iD';

    /**
     * Get the regex pattern for the ID field.
     *
     * @return string
     */
    public function pattern(): string
    {
        return $this->pattern;
    }

    /**
     * Mark the ID field as a UUID.
     *
     * @return $this
     */
    public function uuid(): static
    {
        return $this->matchAs('[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}');
    }

    /**
     * Mark the ID field as a ULID.
     *
     * @return $this
     */
    public function ulid(): static
    {
        return $this->matchAs('[0-7][0-9a-hjkmnp-tv-zA-HJKMNP-TV-Z]{25}');
    }

    /**
     * Set the pattern for the ID field.
     *
     * @param string $pattern
     * @return $this
     */
    public function matchAs(string $pattern): static
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Mark the ID as case-sensitive.
     *
     * @return $this
     */
    public function matchCase(): static
    {
        $this->flags = 'D';

        return $this;
    }

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
    public function match(string $value, string $delimiter = ''): bool
    {
        if (strlen($delimiter) > 0) {
            $delimiter = preg_quote($delimiter);
            return 1 === preg_match(
                "/^{$this->pattern}({$delimiter}{$this->pattern})*$/{$this->flags}",
                $value,
            );
        }

        return 1 === preg_match("/^{$this->pattern}$/{$this->flags}", $value);
    }

    /**
     * Do all the values match the pattern?
     *
     * @param array<string> $values
     * @return bool
     */
    public function matchAll(array $values): bool
    {
        foreach ($values as $value) {
            if ($this->match($value) === false) {
                return false;
            }
        }

        return true;
    }
}
