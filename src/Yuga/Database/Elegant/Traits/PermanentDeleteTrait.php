<?php
namespace Yuga\Database\Elegant\Traits;

trait PermanentDeleteTrait
{
    public function delete($permanent = true)
    {
        return parent::delete(true);
    }
}