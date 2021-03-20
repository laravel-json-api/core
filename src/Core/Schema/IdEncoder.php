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

namespace LaravelJsonApi\Core\Schema;

use Illuminate\Contracts\Routing\UrlRoutable;
use LaravelJsonApi\Contracts\Schema\IdEncoder as IdEncoderContract;

final class IdEncoder implements IdEncoderContract
{

    /**
     * @var IdEncoderContract|null
     */
    private ?IdEncoderContract $encoder;

    /**
     * Safely cast the value to an ID Encoder instance.
     *
     * @param IdEncoderContract|mixed|null $value
     * @return IdEncoderContract
     */
    public static function cast($value): IdEncoderContract
    {
        if ($value instanceof IdEncoderContract) {
            return $value;
        }

        return new self(null);
    }

    /**
     * Safely create a new instance.
     *
     * @param IdEncoderContract|mixed|null $value
     * @return static
     */
    public static function make($value = null): self
    {
        return new self(
            ($value instanceof IdEncoderContract) ? $value : null
        );
    }

    /**
     * IdEncoder constructor.
     *
     * @param IdEncoderContract|null $encoder
     */
    public function __construct(?IdEncoderContract $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @inheritDoc
     */
    public function encode($modelKey): string
    {
        if ($this->encoder) {
            return $this->encoder->encode($modelKey);
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
     * @param $modelKeys
     * @return array
     */
    public function encodeIds($modelKeys): array
    {
        return collect($modelKeys)
            ->map(fn($modelKey) => $this->encodeId($modelKey))
            ->all();
    }

    /**
     * @inheritDoc
     */
    public function decode(string $resourceId)
    {
        if ($this->encoder) {
            return $this->encoder->decode($resourceId);
        }

        return $resourceId;
    }

    /**
     * Decode many resource ids.
     *
     * @param $resourceIds
     * @return array
     */
    public function decodeIds($resourceIds): array
    {
        return collect($resourceIds)
            ->map(fn($resourceId) => $this->decode($resourceId))
            ->reject(fn($id) => is_null($id))
            ->all();
    }

}
