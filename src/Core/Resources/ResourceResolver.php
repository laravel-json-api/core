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

namespace LaravelJsonApi\Core\Resources;

use InvalidArgumentException;
use LaravelJsonApi\Core\Support\Str;
use function class_exists;

final class ResourceResolver
{

    /**
     * @var string
     */
    private static string $defaultResource = JsonApiResource::class;

    /**
     * @var array
     */
    private static array $cache = [];

    /**
     * Manually register the resource class to use for a resource class.
     *
     * @param string $schemaClass
     * @param string $resourceClass
     * @return void
     */
    public static function register(string $schemaClass, string $resourceClass): void
    {
        self::$cache[$schemaClass] = $resourceClass;
    }

    /**
     * Set the default resource class.
     *
     * @param string $resourceClass
     * @return void
     */
    public static function useDefault(string $resourceClass): void
    {
        if (class_exists($resourceClass)) {
            self::$defaultResource = $resourceClass;
            return;
        }

        throw new InvalidArgumentException('Expecting a default resource class that exists.');
    }

    /**
     * Get the default resource class.
     *
     * @return string
     */
    public static function defaultResource(): string
    {
        return self::$defaultResource;
    }

    /**
     * Resolve the fully-qualified resource class from the fully-qualified schema class.
     *
     * @param string $schemaClass
     * @return string
     */
    public function __invoke(string $schemaClass): string
    {
        if (isset(self::$cache[$schemaClass])) {
            return self::$cache[$schemaClass];
        }

        $guess = Str::replaceLast('Schema', 'Resource', $schemaClass);

        if (class_exists($guess)) {
            return self::$cache[$schemaClass] = $guess;
        }

        return self::$cache[$schemaClass] = self::$defaultResource;
    }
}
