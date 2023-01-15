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

namespace LaravelJsonApi\Core\Schema\Concerns;

use Closure;
use InvalidArgumentException;

trait EagerLoadable
{

    /**
     * @var Closure|bool
     */
    private bool $includePath = true;

    /**
     * @param Closure|bool $callback
     * @return $this
     */
    public function canEagerLoad($callback = true): self
    {
        if (!is_bool($callback) && !$callback instanceof Closure) {
            throw new InvalidArgumentException('Expecting a boolean or closure.');
        }

        $this->includePath = $callback;

        return $this;
    }

    /**
     * @return $this
     */
    public function cannotEagerLoad(): self
    {
        return $this->canEagerLoad(false);
    }

    /**
     * Is the relation allowed as an include path?
     *
     * @return bool
     */
    public function isIncludePath(): bool
    {
        if ($this->includePath instanceof Closure) {
            $this->includePath = ($this->includePath)();
        }

        return $this->includePath;
    }
}
