<?php

namespace Yuga\Queue;

interface CanFailInterface
{
    /**
     * method called when a job fails
     * 
     * @param static $job
     * @param string $e
     */
    public function onFailure($job, $message);
}