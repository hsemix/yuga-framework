<?php
namespace Yuga\Database\Elegant\Traits;

trait PermanentDeleteTrait
{
    public function delete()
    {
        return parent::delete(true);
    }
}