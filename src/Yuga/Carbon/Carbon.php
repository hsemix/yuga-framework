<?php

namespace Yuga\Carbon;

use Closure;
use DateTime;
use DatePeriod;
use DateTimeZone;
use JsonSerializable;
use DateTimeInterface;
use InvalidArgumentException;

class Carbon extends DateTime implements JsonSerializable
{
    const DEFAULT_TO_STRING_FORMAT = 'Y-m-d H:i:s';
    protected static $toStringFormat = self::DEFAULT_TO_STRING_FORMAT;

    public function __construct($time = null, $tz = null)
    {
        $timezone = static::safeCreateDateTimeZone($tz);
        parent::__construct($time ?? 'now', $timezone);
    }

    public function __toString()
    {
        return $this->format(self::$toStringFormat);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $carbon = $this;

        return call_user_func(function () use ($carbon) {
            return get_object_vars($carbon);
        });
    }

    /**
     * Format the instance as date
     *
     * @return string
     */
    public function toDateString()
    {
        return $this->format('Y-m-d');
    }

    /**
     * Format the instance as a readable date
     *
     * @return string
     */
    public function toFormattedDateString()
    {
        return $this->format('M j, Y');
    }

    /**
     * Format the instance as time
     *
     * @return string
     */
    public function toTimeString()
    {
        return $this->format('H:i:s');
    }

    /**
     * Format the instance as date and time
     *
     * @return string
     */
    public function toDateTimeString()
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * Format the instance with day, date and time
     *
     * @return string
     */
    public function toDayDateTimeString()
    {
        return $this->format('D, M j, Y g:i A');
    }

    protected static function safeCreateDateTimeZone($object)
    {
        if ($object === null) {
            // Don't return null... avoid Bug #52063 in PHP <5.3.6
            return new DateTimeZone(date_default_timezone_get());
        }

        if ($object instanceof DateTimeZone) {
            return $object;
        }

        if (is_numeric($object)) {
            $tzName = timezone_name_from_abbr(null, $object * 3600, true);

            if ($tzName === false) {
                throw new InvalidArgumentException('Unknown or bad timezone ('.$object.')');
            }

            $object = $tzName;
        }

        $tz = @timezone_open((string) $object);

        if ($tz === false) {
            throw new InvalidArgumentException('Unknown or bad timezone ('.$object.')');
        }

        return $tz;
    }
    
    /**
     * Get a Carbon instance for the current date and time.
     *
     * @param \DateTimeZone|string|null $tz
     *
     * @return static
     */
    public static function now($tz = null)
    {
        return new static(null, $tz);
    }

}