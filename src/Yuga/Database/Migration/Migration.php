<?php

declare(strict_types=1);

namespace Yuga\Database\Migration;

use Yuga\Database\Migration\Schema\Schema;

abstract class Migration
{

    /**
     * @var Schema
     */
    public $schema;

    public function __construct()
    {
        $this->schema = new Schema();
    }

}
