<?php namespace Proxifier\Handlers;

use Collections\Databases;
use Helpers\Shower;
use Proxifier\Exceptions\NotFound;
use Proxifier\Exceptions\Instagram as InstagramException;

class Instagram extends ProxifierHandler
{
    public function __invoke($content, $info, ...$arguments)
    {
        [$url, $queries, $headers, $proxy, $data, $options] = $arguments;

        if($info['http_code'] >= 400)
        {
            throw new NotFound('Page is not found', $arguments);
        }

        $content = json_decode($content, true, 512, JSON_BIGINT_AS_STRING);

        switch(true)
        {
            case $info['http_code'] !== 200:
            case $this->getContentType($info['content_type']) !== self::json_type:
            case !is_array($content):
                throw new InstagramException('Request dont caught. Try again.', $arguments);
        }

        if(!empty($proxy))
        {
            Databases::tags()->proxies
                ->where(['id=' => $proxy['id']])
                ->bind(':p', 1, \PDO::PARAM_INT)
                ->bind(':r', 1, \PDO::PARAM_INT)
                ->update(['processes=processes-:p', 'requests=requests+:r'])
                ->exec();
        }

        ($this->callback)($content, $data);
    }
}