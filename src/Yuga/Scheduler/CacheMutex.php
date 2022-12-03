<?php

namespace Yuga\Scheduler;

use Yuga\Cache\FileCache;
use Yuga\Cache\CacheManager;

class CacheMutex
{
    /**
     * The cache store.
     *
     * @var \Yuga\Cache\FileCache
     */
    public $cache;


    /**
     * Create a new overlapping strategy.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->cache = new FileCache(storage('cache'));

        $cache = new CacheManager(app());
        $cache->registerDriver('cache.default', new FileCache(storage('cache')));
        $cache->setDefaultStoreName('cache.default');

        $this->cache = $cache;
    }

    /**
     * Attempt to obtain a mutex for the given job.
     *
     * @param  \Yuga\Scheduler\Job  $job
     * @return bool
     */
    public function create(Job $job)
    {
        return $this->cache->add(
            $job->mutexName(), true, $job->expiresAt
        );
    }

    /**
     * Determine if a mutex exists for the given job.
     *
     * @param  \Yuga\Scheduler\Job  $job
     * @return bool
     */
    public function exists(Job $job)
    {
        return $this->cache->has($job->mutexName());
    }

    /**
     * Clear the mutex for the given job.
     *
     * @param  \Yuga\Scheduler\Job  $job
     * @return void
     */
    public function forget(Job $job)
    {
        $this->cache->delete($job->mutexName());
    }
}
