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

declare(strict_types=1);

namespace LaravelJsonApi\Core\Document;

use LaravelJsonApi\Core\Json\Hash;
use LaravelJsonApi\Core\Json\Json;
use LogicException;
use function is_null;

final class Document
{

    /**
     * Create a JSON API error object.
     *
     * @param Error|array $value
     * @return Error
     */
    public static function error($value): Error
    {
        if ($value instanceof Error) {
            return $value;
        }

        if (is_array($value)) {
            return Error::fromArray($value);
        }

        throw new LogicException('Unexpected error value.');
    }

    /**
     * Create a list of JSON API error objects.
     *
     * @param ErrorList|Error|array $value
     * @return ErrorList
     */
    public static function errors($value): ErrorList
    {
        if ($value instanceof ErrorList) {
            return $value;
        }

        if ($value instanceof Error) {
            return new ErrorList($value);
        }

        if (is_array($value)) {
            return ErrorList::fromArray($value);
        }

        throw new LogicException('Unexpected error collection value.');
    }


    /**
     * Create a JSON API object.
     *
     * @param JsonApi|Hash|array|string|null $value
     * @return JsonApi
     */
    public static function jsonApi($value): JsonApi
    {
        if ($value instanceof JsonApi) {
            return $value;
        }

        if ($value instanceof Hash) {
            return (new JsonApi())->withMeta($value);
        }

        if (is_string($value) || is_null($value)) {
            return new JsonApi($value);
        }

        if (is_array($value)) {
            return JsonApi::fromArray($value);
        }

        throw new LogicException('Unexpected JSON API member value.');
    }

    /**
     * Create a JSON API links object.
     *
     * @param Links|Link|iterable|null $value
     * @return Links
     */
    public static function links($value): Links
    {
        if ($value instanceof Links) {
            return $value;
        }

        if (is_null($value)) {
            return new Links();
        }

        if ($value instanceof Link) {
            return new Links($value);
        }

        if (is_iterable($value)) {
            return new Links(...$value);
        }

        throw new LogicException('Unexpected links member value.');
    }

    /**
     * Create a JSON API meta object.
     *
     * @param mixed $value
     * @return Hash
     */
    public static function meta($value): Hash
    {
        return Json::hash($value);
    }
}
