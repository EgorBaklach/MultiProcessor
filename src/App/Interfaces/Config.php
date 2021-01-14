<?php namespace App\Interfaces;

interface Config
{
    public function connect();
    public function disconnect();

    public function getCommand();
    public function getOptions();
    public function getHash();
    public function getPath();

    public function getThread();
    public function getObjectThread();

    public function getCache();
    public function getConditionalCache();

    public function getLocalDatabases();
    public function getRemoteDatabases();

    public function throw(\Throwable $e);
}
