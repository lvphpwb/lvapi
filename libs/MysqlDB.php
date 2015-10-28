<?php
class MysqlDB{
    public static $pool = null;
    public static function getInstance($conf){
        if($conf){
            $name = $conf["name"];
            if(!isset(self::$pool[$name])){
                try{
                    self::$pool[$name] = new medoo($conf);
                }catch(Exception $e){
                    throw new Exception("Mysql {$name} " . iconv("gbk", "utf-8", $e->getMessage()), 500);
                }
            }
            return self::$pool[$name];
        }else{
            throw new Exception("No Mysql config", 500);
        }
    }
}