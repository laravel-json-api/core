<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Schema;

use Illuminate\Contracts\Routing\UrlRoutable;
use LaravelJsonApi\Contracts\Schema\ID;
use LaravelJsonApi\Contracts\Schema\IdEncoder as IdEncoderContract;

final class IdParser implements IdEncoderContract
{

    /**
     * @var ID|null
     */
    private ?ID $field;

    /**
     * Safely cast the value to an ID encoder instance.
     *
     * @param IdEncoderContract|mixed|null $value
     * @return IdEncoderContract
     */
    public static function encoder($value): IdEncoderContract
    {
        if ($value instanceof IdEncoderContract) {
            return $value;
        }

        return new self(null);
    }

    /**
     * Fluent constructor.
     *
     * @param ID|null $field
     * @return static
     */
    public static function make(ID $field = null): self
    {
        return new self($field);
    }

    /**
     * IdParser constructor.
     *
     * @param ID|null $field
     */
    public function __construct(?ID $field)
    {
        $this->field = $field;
    }

    /**
     * @inheritDoc
     */
    public function encode($modelKey): string
    {
        if ($this->field instanceof IdEncoderContract) {
            return $this->field->encode($modelKey);
        }

        return (string) $modelKey;
    }

    /**
     * Encode a single id.
     *
     * @param UrlRoutable|string|int $modelKey
     * @return string
     */
    public function encodeId($modelKey): string
    {
        if ($modelKey instanceof UrlRoutable) {
            $modelKey = $modelKey->getRouteKey();
        }

        return $this->encode($modelKey);
    }

    /**
     * Encode many ids.
     *
     * @param mixed $modelKeys
     * @return array
     */
    public function encodeIds($modelKeys): array
    {
        return collect($modelKeys)
            ->map(fn($modelKey) => $this->encodeId($modelKey))
            ->values()
            ->all();
    }

    /**
     * @inheritDoc
     */
    public function decode(string $resourceId)
    {
        if ($this->field instanceof IdEncoderContract) {
            return $this->field->decode($resourceId);
        }

        return $resourceId;
    }

    /**
     * Decode many resource ids.
     *
     * @param mixed $resourceIds
     * @return array
     */
    public function decodeIds($resourceIds): array
    {
        return collect($resourceIds)
            ->map(fn($resourceId) => $this->decodeIfMatch($resourceId))
            ->reject(fn($id) => is_null($id))
            ->values()
            ->all();
    }

    /**
     * Match the provided resource id value.
     *
     * @param string $value
     * @return bool
     */
    public function match(string $value): bool
    {
        if ($this->field) {
            return $this->field->match($value);
        }

        return true;
    }

    /**
     * Decode the provided value if it matches the ID's pattern.
     *
     * @param string $value
     * @return string|int|null
     */
    public function decodeIfMatch(string $value)
    {
        if ($this->match($value)) {
            return $this->decode($value);
        }

        return null;
    }

}
