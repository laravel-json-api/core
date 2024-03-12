<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Server;

interface Repository
{
    /**
     * Retrieve the named server.
     *
     * This method MAY use thread-caching to optimise performance
     * where multiple servers may be used.
     *
     * @param string $name
     * @return Server
     */
    public function server(string $name): Server;

    /**
     * Retrieve the named server, to use once.
     *
     * This method MUST NOT use thread-caching.
     *
     * @param string $name
     * @return Server
     */
    public function once(string $name): Server;
}
