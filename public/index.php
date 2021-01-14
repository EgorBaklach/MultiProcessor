<?php if(!empty($_SERVER['SERVER_NAME'])) throw new \LogicException("Executable only by Cron");

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$runner = new App\Runner();

try
{
    $runner->open();

    $thread = $runner->init();

    $runner->run($thread);

    if(!empty($runner->method()))
    {
        $result = $thread->{$runner->method()}(...$runner->arguments());

        if(!empty($runner->SubMethod()))
        {
            $thread->{$runner->SubMethod()}($result);
        }
    }

    $runner->close()->disconnect();
}
catch(Throwable $e)
{
    $runner->throw($e);
}