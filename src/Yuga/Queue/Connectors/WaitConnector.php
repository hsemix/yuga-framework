<?php

namespace Yuga\Queue\Connectors;

use Yuga\Queue\Queues\WaitQueue;


class WaitConnector extends BaseConnector
{

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Yuga\Queue\Contracts\QueueInterface
     */
    public function connect(array $config)
    {
        return new WaitQueue;
    }

    /**
	 * send message to queueing system.
	 *
	 * @param array  $data
	 * @param string $queue
	 */
	public function send($data, string $queue = '')
    {

    }

	/**
	 * Fetch message from queueing system.
	 * When there are no message, this method will return (won't wait).
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	public function fetch(callable $callback, string $queue = '', $shouldStop = false) : bool
    {

    }

	/**
	 * Receive message from queueing system.
	 * When there are no message, this method will wait.
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	public function receive(callable $callback, string $queue = '') : bool
    {

    }

	public function reset()
    {
        
    }

}
