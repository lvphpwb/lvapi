<?php
class Config{
    public static $conf = array(
        "authredis" => array("name"=>"authredis", "host"=>"127.0.0.1", "port"=>"14000", "timeout"=>5, "prefix"=>"auth:"),
        "mysqltest" => array(
            'name' => "mysqltest",
            'database_type' => 'mysql',
            'database_name' => 'lvcms',
            'server' => '127.0.0.1',
            'username' => 'test',
            'password' => 'test',
            'charset' => 'utf8',
            'port' => 3306
        )
    );
}