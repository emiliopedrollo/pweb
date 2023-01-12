<?php

namespace App;

use PDO;

class DB
{
    public readonly PDO $connection;

    protected array $config;

    public function __construct()
    {
        $this->config = config('database');
        $this->connection = new PDO(
            sprintf('mysql:host=%s;dbname=%s', $this->config['db_host'], $this->config['db_name']),
            $this->config['db_user'],
            $this->config['db_pass'],
            $this->config['db_opts']
        );
    }

}
