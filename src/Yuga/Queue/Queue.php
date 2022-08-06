<?php 

namespace Yuga\Queue;

use Yuga\Queue\Exceptions\QueueException;

/**
 * Queue class.
 */
class Queue implements QueueInterface
{
	/**
	 * Config object.
	 *
	 * @var 
	 */
	protected $config;

	/**
	 * Config of the connection connection to use
	 *
	 * @var array
	 */
	protected $connectionConfig;

	/**
	 * Constructor.
	 *
	 * @param $config
	 * @param string|array  $connection The name of the connection to use,
	 *                              or an array of configuration settings.
	 */
	public function __construct($config, $connection = '')
	{
        // print_r($config);

        // die();
		if (is_array($connection)) {
			$connectionConfig = $connection;
			$connection       = 'custom';
		} else {
			if ($connection === '') {
				$connection = (string) $config['default'];
			}

            // print_r($config[$connection]);
            // die();

			if (isset($config['connections'][$connection])) {
				$connectionConfig = $config['connections'][$connection];
			} else {
				throw new QueueException('Invalid connection configuration');
			}
		}

		$this->connectionConfig = $connectionConfig;
		$this->config           = $config;
	}

	/**
	 * connecting queueing system.
	 *
	 * @return CodeIgniter\Queue\Handlers\BaseHandler
	 */
	public function connect()
	{
		return app()->resolve(
			'\\Yuga\\Queue\\Connectors\\' . ucfirst($this->connectionConfig['driver']) . 'Connector', 
			[$this->connectionConfig, $this->config]
		);//->connect($this->connectionConfig);
	}
}
