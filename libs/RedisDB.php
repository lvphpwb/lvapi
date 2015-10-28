<?php
class RedisDB{
    public static $pool = null;
    public static function getInstance($conf){
        if($conf){
            $name = $conf['name'];
            if(!isset(self::$pool[$name])){
                self::$pool[$name] = new Redis();
                $result = self::$pool[$name]->connect($conf['host'], $conf['port'], $conf['timeout']);
                if(!$result){
                    throw new Exception("Redis {$name} connect error", 500);
                }
                if($conf['prefix']){
                    self::$pool[$name]->setOption(Redis::OPT_PREFIX, $conf['prefix']);
                }
            }
            return self::$pool[$name];
        }else{
            throw new Exception("No Redis config", 500);
        }
    }
}