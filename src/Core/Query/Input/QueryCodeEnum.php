<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

namespace LaravelJsonApi\Core\Query\Input;

enum QueryCodeEnum: string
{
    case Many = 'many';
    case One = 'one';
    case Related = 'related';
    case Relationship = 'relationship';
}
