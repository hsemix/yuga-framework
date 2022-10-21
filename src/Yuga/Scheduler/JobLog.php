<?php

namespace Yuga\Scheduler;

class JobLog
{
    /**
     * @var Job
     */
    protected Job $task;

    /**
     * @var string
     */
    protected $output;

    /**
     * @var \DateTime
     */
    protected $runStart;

    /**
     * @var \DateTime
     */
    protected $runEnd;

    /**
     * The exception thrown during execution, if any.
     *
     * @var \Throwable
     */
    protected $error;

    /**
     * TaskLog constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Returns the duration of the task in mm:ss format.
     *
     * @throws \Exception
     *
     * @return string
     */
    public function duration()
    {
        $diff = $this->runEnd->getTimestamp() - $this->runStart->getTimestamp();

        // $dif = $this->runEnd->difference( $this->runStart );

        // $minutes = (int) $dif->getMinutes( true );
        // $seconds = $dif->getSeconds( true );

        // Since $seconds includes the minutes, calc the extra
        // $seconds = $seconds - ( $minutes * 60 );

        return str_pad((string) $diff, 2, '0', STR_PAD_LEFT); // . ':' . str_pad( (string) $seconds, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Magic getter.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }
    }
}
