<?php 

namespace Yuga\Exceptions;

use RuntimeException;

class SchedulerException extends RuntimeException
{
	public static function forInvalidTaskType(string $type)
	{
		return new static($type . 'is not a valid type of task');
	}

	public static function forInvalidExpression(string $type)
	{
		return new static($type . 'is not a valid expression');
	}
}