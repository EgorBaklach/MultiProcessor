<?php namespace Proxifier;

class Requester
{
    private $multi;
    private $queue;
    private $options;

    private static $headers = [];
    private static $proxy = [];

    public function __construct(array $options = [])
    {
        $this->multi = curl_multi_init();
        $this->queue = new \SplQueue();
        $this->options = $options ?? [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => self::$headers,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT => 0
        ];
    }

    public function headers($headers)
    {
        $formatted = [];

        foreach($headers as $key => $val)
        {
            $formatted[] = implode(':', [$key, $val]);
        }

        self::$headers = $formatted;

        return $this;
    }

    public function proxy($type, $address, $port, $tunnel)
    {
        self::$proxy = [
            'type' => $type,
            'address' => $address,
            'port' => $port,
            'tunnel' => $tunnel
        ];

        return $this;
    }

    public function auth($method, $user, $pass)
    {
        self::$proxy['auth'] = [
            'method' => $method,
            'user' => $user,
            'pass' => $pass
        ];

        return $this;
    }

    public function set($url, array $data, array $options)
    {
        $init = curl_init($url);

        curl_setopt_array($init, $options ?? $this->options);

        if(!empty(self::$proxy['address']))
        {
            curl_setopt_array($init, [
                CURLOPT_PROXYTYPE => self::$proxy['type'],
                CURLOPT_PROXY => self::$proxy['address'],
                CURLOPT_PROXYPORT => self::$proxy['port'],
                CURLOPT_HTTPPROXYTUNNEL => self::$proxy['tunnel'],
                CURLOPT_PROXYAUTH => self::$proxy['auth']['method'],
                CURLOPT_PROXYUSERPWD => self::$proxy['auth']['user'].':'.self::$proxy['auth']['pass']
            ]);
        }

        curl_multi_add_handle($this->multi, $init);

        $this->queue->enqueue([$init, $data]);
    }

    public function exec(callable $callback)
    {
        if(!is_resource($this->multi)) return;

        do
        {
            curl_multi_exec($this->multi, $running);
            curl_multi_select($this->multi);
        }
        while($running > 0);

        while(!$this->queue->isEmpty())
        {
            list($init, $data) = $this->queue->dequeue();

            curl_multi_remove_handle($this->multi, $init);

            call_user_func_array($callback, [curl_multi_getcontent($init), curl_getinfo($init), $data]);

            curl_close($init);

            usleep(mt_rand(100, 350));
        }

        $this->multi = null;
    }
}
