<?php

declare(strict_types=1);

namespace Yuga\Queue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class QueueJob {}
