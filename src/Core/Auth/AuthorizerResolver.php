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

namespace LaravelJsonApi\Core\Auth;

use InvalidArgumentException;
use LaravelJsonApi\Core\Support\Str;
use function class_exists;

final class AuthorizerResolver
{

    /**
     * The default authorizer.
     *
     * @var string
     */
    private static string $defaultAuthorizer = Authorizer::class;

    /**
     * @var array
     */
    private static array $cache = [];

    /**
     * Manually register the authorizer class for a schema class.
     *
     * @param string $schemaClass
     * @param string $authorizerClass
     * @return void
     */
    public static function register(string $schemaClass, string $authorizerClass): void
    {
        self::$cache[$schemaClass] = $authorizerClass;
    }

    /**
     * Set the default authorizer class.
     *
     * @param string $authorizerClass
     * @return void
     */
    public static function useDefault(string $authorizerClass): void
    {
        if (class_exists($authorizerClass)) {
            self::$defaultAuthorizer = $authorizerClass;
            return;
        }

        throw new InvalidArgumentException('Expecting a default authorizer class that exists.');
    }

    /**
     * Resolve the fully-qualified authorizer class from the fully-qualified schema class.
     *
     * @param string $schemaClass
     * @return string
     */
    public function __invoke(string $schemaClass): string
    {
        if (isset(self::$cache[$schemaClass])) {
            return self::$cache[$schemaClass];
        }

        $guess = Str::replaceLast('Schema', 'Authorizer', $schemaClass);

        if (class_exists($guess)) {
            return self::$cache[$schemaClass] = $guess;
        }

        return self::$cache[$schemaClass] = self::$defaultAuthorizer;
    }
}
