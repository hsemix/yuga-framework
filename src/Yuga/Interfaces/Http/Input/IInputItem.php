<?php

declare(strict_types=1);

/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Interfaces\Http\Input;

interface IInputItem
{

    public function getIndex();

    public function setIndex($index);

    public function getName();

    public function setName($name);

    public function getValue();

    public function __toString();

}
