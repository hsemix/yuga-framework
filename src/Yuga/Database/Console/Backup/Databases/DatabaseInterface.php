<?php

namespace Yuga\Database\Console\Backup\Databases;

interface DatabaseInterface
{
    /**
     * Create a database dump.
     *
     * @return bool
     */
    public function dump($destinationFile);

    /**
     * Restore a database dump.
     *
     * @return bool
     */
    public function restore($sourceFile);

    /**
     * Return the file extension of a dump file (sql, ...).
     *
     * @return string
     */
    public function getFileExtension();
}
