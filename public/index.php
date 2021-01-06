<?php if(!empty($_SERVER['SERVER_NAME'])) throw new \LogicException("Executable only by Cron");

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$config = new App\Config();
$runner = new App\Runner();

try
{
    if(!$config->getReady())
    {
        throw new App\Exceptions\InternalException('Process is not ready');
    }

    $config->connect();

    $thread = $config->initProcess();

    $runner->run($thread);

    if(!empty($runner->getMethod()))
    {
        if(!method_exists($thread, $runner->getMethod()))
        {
            throw new \Exception("Method {$runner->getMethod()}: does not exist in Process");
        }

        $result = call_user_func_array([$thread, $runner->getMethod()], $runner->getAttr());
    }

    if(!empty($runner->getSubMethod()))
    {
        call_user_func([$thread, $runner->getSubMethod()], $result);
    }

    $config->disconnect();
}
catch(Throwable $e)
{
    $config->throwable($e);
}