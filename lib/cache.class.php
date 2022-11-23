<?php

class Cache
{

    function __construct()
    {
        $this->mc = new Memcached('erp');
        if (!sizeof($this->mc->getServerList())) {
            $this->mc->addServers(array(
                array('localhost',11211),
            ));
        }
    }

    function get($key)
    {
        $result = $this->mc->get(strtolower(trim($key)));
        if($result)
            return(($result));
    }

    function set($key, $data)
    {
        $this->mc->set(strtolower(trim($key)), ($data));
    }

    function get_json($key)
    {
        $result = $this->get($key);
        return(json_decode($result, true));
    }

    function set_json($key, $data)
    {
        $this->set($key, json_encode($data));
    }

    function print($key, $generateFunction, $skipCache = false)
    {
        $content = json_decode($this->get($key), true);
        if($skipCache || !$content || $content['time'] < time()-30)
        {
            ob_start();
            $generateFunction();
            $content['text'] = ob_get_clean();
            $content['time'] = time();
            $this->set($key, json_encode($content));
        }
        print($content['text']);
    }

}


