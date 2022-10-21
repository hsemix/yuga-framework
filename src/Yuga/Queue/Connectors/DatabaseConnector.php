<?php

namespace Yuga\Queue\Connectors;

use Yuga\Database\Query\DB;

/**
 * Queue handler for database.
 */
class DatabaseConnector extends BaseConnector
{
    protected const STATUS_WAITING = 10;
    protected const STATUS_EXECUTING = 20;
    protected const STATUS_DONE = 30;
    protected const STATUS_FAILED = 40;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var int
     */
    protected $timeout;

    /**
     * @var int
     */
    protected $maxRetries;

    /**
     * @var int
     */
    protected $deleteDoneMessagesAfter;

    /**
     * @var
     */
    protected $db;

    /**
     * constructor.
     *
     * @param array         $connectionConfig
     * @param \Config\Queue $config
     */
    public function __construct($connectionConfig, $config)
    {
        parent::__construct($connectionConfig, $config);
        $settings = $config['settings'];
        $this->table = $connectionConfig['table'];

        $this->timeout = $settings['timeout'];
        $this->maxRetries = $settings['maxRetries'];
        $this->deleteDoneMessagesAfter = $settings['deleteDoneMessagesAfter'];

        $this->db = new DB();
    }

    /**
     * send message to queueing system.
     *
     * @param array  $data
     * @param string $queue
     */
    public function send($data, string $queue = '')
    {
        if ($queue === '') {
            $queue = $this->defaultQueue;
        }

        // $this->db->transStart();

        $datetime = date('Y-m-d H:i:s');

        $this->db->table($this->table)->insert([
            'queue'        => $queue,
            'status'       => self::STATUS_WAITING,
            'weight'       => 100,
            'attempts'     => 0,
            'available_at' => $this->available_at->format('Y-m-d H:i:s'),
            'data'         => json_encode($data),
            'created_at'   => $datetime,
            'updated_at'   => $datetime,
        ]);

        // return true;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Fetch message from queueing system.
     * When there are no message, this method will return (won't wait).
     *
     * @param callable $callback
     * @param string   $queue
     *
     * @return bool whether callback is done or not.
     */
    public function fetch(callable $callback, string $queue = ''): bool
    {
        $row = $this->db->table($this->table)
            ->where('queue', $queue !== '' ? $queue : $this->defaultQueue)
            ->where('status', self::STATUS_WAITING)
            ->where('available_at', '<', date('Y-m-d H:i:s'))
            ->orderBy('weight')
            ->orderBy('id')
            ->limit(1)
            ->first();

        //if there is nothing else to run at the moment return false.
        if (!$row) {
            $this->housekeeping();
            usleep($this->timeout * 1000000);

            return true;
        }

        //set the status to executing if it hasn't already been taken.
        $this->db->table($this->table)
            ->where('id', (int) $row->id)
            ->where('status', (int) self::STATUS_WAITING)
            ->update([
                'status'     => self::STATUS_EXECUTING,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        //don't run again if its already been taken.

        $data = json_decode($row->data, true);

        //if the callback doesn't throw an exception mark it as done.
        try {
            $callback($data);

            $this->db->table($this->table)
                ->where('id', $row->id)
                ->update([
                    'status'     => self::STATUS_DONE,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->fireOnSuccess($data, $row);
        } catch (\Throwable $e) {
            //track any exceptions into the database for easier troubleshooting.
            $error = (new \DateTime())->format('Y-m-d H:i:s')."\n".
                    "{$e->getCode()} - {$e->getMessage()}\n\n".
                    "file: {$e->getFile()}:{$e->getLine()}\n".
                    "------------------------------------------------------\n\n";

            $this->db->table($this->table)
                ->where('id', $row->id)
                ->update(['error' => 'error: '.$error]);
            $this->fireOnFailure($e, $data, $row);

            throw $e;
        }

        //there could be more to run so return true.
        return true;
    }

    /**
     * Receive message from queueing system.
     * When there are no message, this method will wait.
     *
     * @param callable $callback
     * @param string   $queue
     *
     * @return bool whether callback is done or not.
     */
    public function receive(callable $callback, string $queue = ''): bool
    {
        while (!$this->fetch($callback, $queue)) {
            usleep(1000000);
        }

        return true;
    }

    /**
     * housekeeping.
     *
     * clean up the database at the end of each run.
     */
    public function housekeeping()
    {
        //update executing statuses to waiting on timeout before max retry.
        $this->db->table($this->table)
            ->where('status', self::STATUS_EXECUTING)
            ->where('updated_at', '<', date('Y-m-d H:i:s', time() - $this->timeout))
            ->where('attempts', '<', $this->maxRetries)
            ->update([
                'attempts'   => $this->db->raw('`attempts` + 1'),
                'status'     => self::STATUS_WAITING,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        //update executing statuses to failed on timeout at max retry.
        $this->db->table($this->table)
            ->where('status', self::STATUS_EXECUTING)
            ->where('updated_at', '<', date('Y-m-d H:i:s', time() - $this->timeout))
            ->where('attempts', '>=', $this->maxRetries)
            ->update([
                'attempts'   => $this->db->raw('`attempts` + 1'),
                'status'     => self::STATUS_FAILED,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        //Delete messages after the configured period.
        if ($this->deleteDoneMessagesAfter !== false) {
            $this->db->table($this->table)
                ->where('status', self::STATUS_DONE)
                ->where('updated_at', '<', date('Y-m-d H:i:s', time() - $this->deleteDoneMessagesAfter))
                ->delete();
        }
    }

    /**
     * Reset all the failed jobs.
     */
    public function reset()
    {
        //set the status to executing if it hasn't already been taken.
        $this->db->table($this->table)
            ->where('status', (int) self::STATUS_FAILED)
            ->update([
                'status'     => self::STATUS_WAITING,
                'attempts'   => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
