<?php
class IndexController {

    public function indexAction(){
        return "ok";
    }
    //note PC获取二维码参数
    public function testAction($mid, $pcname){
        $redis = RedisDB::getInstance(Config::$conf["authredis"]);
        $redis->set("test", "test");
        $data = $redis->get("test");
        var_dump($data);
        $db = MysqlDB::getInstance(Config::$conf["mysqltest"]);
        $data = $db->select('systemuser', "*");
        var_dump($data);
        return "";
    }
}