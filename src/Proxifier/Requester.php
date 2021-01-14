<?php namespace Proxifier;

class Requester
{
    /** @var \CurlMultiHandle|false|resource */
    private $multi;

    /** @var \SplQueue  */
    private $queue;

    private $queries;
    private $headers;
    private $proxy;

    public function __construct(array $options = null)
    {
        $this->multi = curl_multi_init();
        $this->queue = new \SplQueue();
        $this->options = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT => 0
        ];

        if(!empty($options))
        {
            $this->options += $options;
        }
    }

    public function headers(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    public function queries(array $queries)
    {
        $this->queries = $queries;

        return $this;
    }

    public function proxy(array $proxy)
    {
        $this->proxy = $proxy;

        return $this;
    }

    public function set($url, $data = null, array $options = null)
    {
        if(!empty($this->queries))
        {
            $url .= '?'.urldecode(http_build_query($this->queries));
        }

        $init = curl_init($url);

        curl_setopt_array($init, $options ?? $this->options);

        if(!empty($this->headers))
        {
            $headers = [];

            foreach($this->headers as $key => $val)
            {
                $headers[] = implode(':', [$key, $val]);
            }

            curl_setopt($init, CURLOPT_HTTPHEADER, $headers);
        }

        if(!empty($this->proxy['ip']))
        {
            curl_setopt_array($init, [
                CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
                CURLOPT_PROXY => $this->proxy['ip'],
                CURLOPT_PROXYPORT => $this->proxy['port'],
                CURLOPT_HTTPPROXYTUNNEL => true
            ]);

            if(!empty($this->proxy['user']))
            {
                curl_setopt_array($init, [
                    CURLOPT_PROXYAUTH => CURLAUTH_BASIC,
                    CURLOPT_PROXYUSERPWD => $this->proxy['user'].':'.$this->proxy['pass']
                ]);
            }
        }

        curl_multi_add_handle($this->multi, $init);

        $this->queue->enqueue([$init, [$url, $this->queries, $this->headers, $this->proxy, $data, $options]]);
    }

    public function exec(callable $invoke, callable $throwback)
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
            [$init, $data] = $this->queue->dequeue();

            curl_multi_remove_handle($this->multi, $init);

            try
            {
                $invoke(curl_multi_getcontent($init), curl_getinfo($init), ...$data);
            }
            catch (\Throwable $e)
            {
                $throwback($e);
            }

            curl_close($init);

            usleep(mt_rand(100, 350));
        }
    }
}
