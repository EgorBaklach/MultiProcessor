<?php namespace Proxifier\Handlers;

use Proxifier\Exceptions\NotFound;
use Proxifier\Exceptions\Instagram as InstagramException;
use Proxifier\Exceptions\Success;

class Instagram extends ProxifierHandler
{
    public function __invoke($content, $info, ...$arguments)
    {
        [$url, $queries, $headers, $proxy, $data, $options] = $arguments;

        if($info['http_code'] >= 400)
        {
            throw new NotFound($arguments);
        }

        $content = json_decode($content, true, 512, JSON_BIGINT_AS_STRING);

        switch(true)
        {
            case $info['http_code'] !== 200:
            case $this->getContentType($info['content_type']) !== self::json_type:
            case !is_array($content):
                throw new InstagramException($arguments);
        }

        ($this->callback)($content, $data);

        throw new Success($arguments);
    }
}