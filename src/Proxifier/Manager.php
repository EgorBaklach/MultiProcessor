<?php namespace Proxifier;

use DB\Handlers\AgentsHandler;
use DB\Handlers\DefaultHandler;
use Psr\SimpleCache\CacheInterface;

class Manager
{
    /** @var Requester */
    private $requester;

    private $data = [
        'proxies' => [
            [
                ['id', 'ip', 'port', 'user', 'pass'],
                [
                    ['type=' => 'mobile'],
                    ['active=' => 'Y']
                ],
                ['last_request' => 'asc', 'id' => 'asc']
            ],
            DefaultHandler::class
        ],
        'agents' => [
            [['*'], null, ['id']],
            AgentsHandler::class
        ],
    ];

    public function __construct(CacheInterface $cache)
    {
        $this->requester = new Requester();

        foreach($this->data as $name => [$query, $handler])
        {

        }

        /*list($select, $where, $orders) = $conditions['query'];

        $query = DB::table($table)
            ->select($select)
            ->where($where);

        foreach($orders as $order)
        {
            $query->orderBy($order);
        }

        $values = $query->get();

        if(!empty($conditions['handler']))
        {

            $handler = new $conditions['handler']($values);

            $values = $handler->process();
        }

        return $values;*/
    }
}
