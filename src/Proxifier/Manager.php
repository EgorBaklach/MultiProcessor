<?php namespace Proxifier;

use Collections\Databases;
use DB\Handlers\Agents;
use DB\Handlers\Current;
use DB\Handlers\DBHandler;
use Helpers\Date;
use Phpfastcache\Helper\CacheConditionalHelper;
use Proxifier\Exceptions\Instagram as InstagramException;
use Proxifier\Exceptions\NotFound as NotFoundException;
use Proxifier\Exceptions\Success as SuccessException;
use Proxifier\Exceptions\ProxifierException;
use Proxifier\Handlers\ProxifierHandler;
use Psr\SimpleCache\CacheInterface;

/**
 * Class Manager
 * @package Proxifier
 * @property DBHandler $proxies
 * @property DBHandler $agents
 */
class Manager
{
    /** @var Requester */
    private $requester;

    /** @var CacheInterface */
    private $cache;

    /** @var CacheConditionalHelper */
    private $CacheConditional;

    /** @var \SplQueue */
    private $queue;

    /** @var Databases */
    private $DB;

    private $data = [
        'proxies' => [
            ['id', 'ip', 'port', 'user', 'pass'],
            ['type=' => 'mobile', 'active=' => 'Y'],
            ['last_request' => 'asc', 'id' => 'asc'],
            Current::class
        ],
        'agents' => [['*'], null, ['id'], Agents::class]
    ];

    public function __construct(Databases $DB, CacheInterface $cache, CacheConditionalHelper $conditinalCache, array $options = null)
    {
        $this->DB = $DB;
        $this->cache = $cache;
        $this->CacheConditional = $conditinalCache;
        $this->requester = new Requester($options);
        $this->queue = new \SplQueue();

        foreach($this->data as $table => $queries)
        {
            $this->data[$table] = $this->CacheConditional->get(implode('', ['CacheUtils', ucfirst($table), 'Default']), function () use ($table, $queries)
            {
                [$select, $where, $orders, $handler] = $queries;

                return new $handler($this->DB->{$table}->where($where ?? NULL)->order($orders)->select($select)->exec());
            });
        }
    }

    public function __get($name)
    {
        if(empty($this->data[$name]))
        {
            throw new \Exception('DBHandler '.$name.' is not available');
        }

        return $this->data[$name];
    }

    public function enqueue(...$data)
    {
        [$url, $queries, $headers, $proxy, $data, $options] = $data;

        $this->queue->enqueue([$url, $queries, $headers ?? $this->getHeaders(), $proxy ?? $this->getProxy(), $data, $options]);

        return $this;
    }

    public function getProxy()
    {
        $n = mt_rand(0, $this->proxies->count() - 1);

        return $this->proxies[$n];
    }

    public function getAgent($type = 'desktop')
    {
        $n = mt_rand(0, count($this->agents[$type ?: 'mobile']) - 1);

        return $this->agents[$type ?: 'mobile'][$n]['name'];
    }

    public function getHeaders($type = false, $lang = false, array $cookies = []): array
    {
        $headers = ['user-agent' => $this->getAgent($type)];

        if(!empty($cookies))
        {
            $headers['cookie'] = urldecode(http_build_query($cookies, false, '; '));
        }

        if(in_array($lang, ['ru', 'en']))
        {
            $headers['accept-language'] = $lang;
        }

        return $headers;
    }

    public function init(ProxifierHandler $handler)
    {
        if($this->queue->isEmpty()) return null;

        while(!$this->queue->isEmpty())
        {
            [$url, $queries, $headers, $proxy, $data, $options] = $this->queue->dequeue();

            if(!empty($queries))
            {
                $this->requester->queries($queries);
            }

            if(!empty($proxy))
            {
                $this->DB->proxies
                    ->where(['id=' => $proxy['id']])
                    ->bind(':p', 1, \PDO::PARAM_INT)
                    ->update(['processes=processes+:p'])
                    ->exec();

                $this->requester->proxy($proxy);
            }

            if(!empty($headers))
            {
                $this->requester->headers($headers);
            }

            $this->requester->set($url, $data, $options);
        }

        $this->requester->exec($handler, function(ProxifierException $e)
        {
            if($e instanceof InstagramException)
            {
                $this->enqueue(...$e->getAttr());
            }

            if(!empty($e->proxy))
            {
                $query = $this->DB->proxies->where(['id=' => $e->proxy['id']])->bind(':process', 1, \PDO::PARAM_INT);
                $update = ['processes=processes-:process', 'last_request' => Date::correct('Y-m-d H:i:s')];

                switch(true)
                {
                    case $e instanceof InstagramException:
                        $query->bind(':block', 1, \PDO::PARAM_INT);
                        $update[] = 'blocked=blocked+:block';
                        break;
                    case $e instanceof NotFoundException:
                        $query->bind(':inactive', 1, \PDO::PARAM_INT);
                        $update[] = 'inactives=inactives+:inactive';
                        break;
                    case $e instanceof SuccessException:
                        $query->bind(':request', 1, \PDO::PARAM_INT);
                        $update[] = 'requests=requests+:request';
                        break;
                }

                $query->update($update)->exec();
            }
        });

        if(!$this->queue->isEmpty())
        {
            $this->init($handler);
        }
    }
}
