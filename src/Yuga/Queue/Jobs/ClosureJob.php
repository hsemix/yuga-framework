<?php

namespace Yuga\Queue\Jobs;

use Yuga\Queue\DispatchableTrait;
use Opis\Closure\SerializableClosure;
use Yuga\Interfaces\Queue\ShouldQueueInterface;

class ClosureJob implements ShouldQueueInterface
{
    use DispatchableTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public $job, public $data = [])
    {
        $this->job = new SerializableClosure($job);
    }

    /**
     * Run the job.
     *
     * @return mixed
     */
    public function run()
    {   
        return call_user_func($this->job, $this->data);
    }
}
