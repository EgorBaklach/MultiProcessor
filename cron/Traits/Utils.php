<?php namespace Cron\Traits;

use App\Exceptions\InstagramException;
use App\Exceptions\LogicException;
use Cron\Abstracts\Cron;
use Cron\QueryHandlers\DefaultHandler;
use Cron\QueryHandlers\AgentsHandler;
use Cron\QueryHandlers\HandlerInterface;
use DB\Collection;
use DB\Factory;
use Unirest\Request;

/**
 * Trait Threads
 * @package Cron\Traits
 * @property array $proxies
 * @property array $accounts
 * @property array $sections
 * @property array $agents
 */
trait Utils
{
    protected $headers;
    protected $queries;

    private $data = [
        'proxies' => [
            'query' => [
                ['id', 'ip', 'port', 'user', 'pass'],
                ['date_inactive>=NOW()', 'type=' => 'mobile', 'active=' => 'Y'],
                ['last_request' => 'ASC', 'id' => 'ASC']
            ],
            'handler' => DefaultHandler::class
        ],
        'agents' => [
            'query' => [['*'], false, ['id' => 'ASC']],
            'handler' => AgentsHandler::class
        ],
    ];

    private $tables = [];

    public function exec()
    {
        foreach($this->data as $table => $conditions)
        {
            /** @var Cron $this */
            $hash = implode('', ['CacheUtils', ucfirst($table), 'Default']);
            $values = $this->cache->get($hash) ?: [];

            $this->tables[$table] = new Factory($table, Collection::tools());

            if(empty($values))
            {
                /** @var \PDOStatement $rs */
                $rs = call_user_func_array([$this->tables[$table], 'select'], $conditions['query'])->exec();

                if(!class_exists($conditions['handler']))
                {
                    throw new LogicException('Class '.$conditions['handler'].' not found');
                }

                /** @var HandlerInterface $handler */
                $handler = new $conditions['handler']($rs);

                $values = $handler->process();

                if(!empty($values))
                {
                    $this->cache->set($hash, $values, self::TTL_10MIN);
                }
            }

            $this->data[$table] = $values;
        }

        return $this->getAttr();
    }

    public function getRandUserAgent($type)
    {
        $type = $type === 'desktop' ? 'desktop' : 'mobile';

        $key = mt_rand(0, count($this->agents[$type])-1);

        return $this->agents[$type][$key]['name'];
    }

    public function setDefaultHeaders($type = false, $lang = false, array $cookies = []): self
    {
        $this->headers = [
            'user-agent' => $this->getRandUserAgent($type),
            'cookie' => http_build_query($cookies, false, '; ')
        ];

        if(in_array($lang, ['ru', 'en']))
        {
            $this->headers['accept-language'] = $lang;
        }

        return $this;
    }

    public function setQueries(array $params = []): self
    {
        $this->queries = $params;

        return $this;
    }

    protected function getParsedContentType($contentType)
    {
        if(!is_array($contentType)) $contentType = (array) $contentType;

        list($type, $charset) = array_map('trim', explode(';', array_shift($contentType)));

        return $type;
    }

    protected function request($url, callable $action)
    {
        $DBProxies = $this->getTable('proxies');

        while($proxy = current($this->data['proxies']))
        {
            Request::proxy($proxy['ip'], $proxy['port'], CURLPROXY_HTTP, true);
            Request::proxyAuth($proxy['user'], $proxy['pass'], CURLAUTH_BASIC);
            Request::timeout(mt_rand(1, 5));

            try
            {
                $DBProxies->update(['processes=processes+1'], ['id=' => $proxy['id']])->exec();

                $result = $action(Request::get($url, $this->headers, $this->queries));
            }
            catch(\Throwable $e)
            {
                $update = [
                    'inactives=inactives+1',
                    'processes=processes-1',
                    'last_request' => date('Y-m-d H:i:s')
                ];

                if($e instanceof InstagramException)
                {
                    $update[] = 'blocked=blocked+1';
                }

                $DBProxies->update($update, ['id=' => $proxy['id']])->exec();
                next($this->data['proxies']);
                //Log::add2log($e->getMessage());
                Request::proxy('');
                continue;
            }

            $DBProxies->update(['requests=requests+1', 'processes=processes-1'], ['id=' => $proxy['id']])->exec();

            Request::proxy('');
            break;
        }

        return $result;
    }

    protected function getTable($name): Factory
    {
        return $this->tables[$name];
    }

    protected function shuffle($name): self
    {
        shuffle($this->data[$name]);

        return $this;
    }

    public function __get($name)
    {
        return $this->data[$name] ?: false;
    }
}