<?php
date_default_timezone_set('Asia/Shanghai');
define("ROOT_PATH", dirname(__FILE__));
define("LOG_NAME", "test");
define("LOG_PATH", ROOT_PATH . "/logs");
define("CONSUME_TIME_LIMIT", 2);
define("START_TIME", microtime(true));

$server_type = get_cfg_var("web.server_type");
$conf_file = ROOT_PATH . "/config/config_" . basename($server_type) . ".php";
if(!is_file($conf_file)){
    exit("No Config File!");
}

require(ROOT_PATH . '/libs/Application.php');
Application::init();
Application::load(array("dir"=>array(ROOT_PATH . "/libs", ROOT_PATH . "/controller"), "file"=>array("Config"=>$conf_file)));
register_shutdown_function('Application::shutdown');
set_error_handler('Application::catchErrHandler');

$result = array("errno"=>0, "msg"=>"", "data"=>"");
try{
    //note 程序运行
    $app = new Application();
    $data = $app->run();
    $data && $result['data'] = $data;
}catch(Exception $e){
    $result['errno'] = $e->getCode();
    $result['msg'] = $e->getMessage();
}
//note 输出结果
Application::display($result);
exit();