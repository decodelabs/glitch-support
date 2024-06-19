<?php

/**
 * @package GlitchSupport
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SensitiveProperty
{
}
