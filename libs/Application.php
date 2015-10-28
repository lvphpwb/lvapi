<?php
defined('ROOT_PATH') || exit("NO ROOT_PATH!");
defined('LOG_PATH') || exit("NO LOG_PATH!");
defined('LOG_NAME') || exit("NO LOG_NAME!");
class Application{
    protected $_controller = null;
    protected $_method = null;
    protected $_argTypes = array("p"=>"post", "g"=>"get", "c"=>"cookie");
    protected static $_inputs = null;
    protected static $_directories = array();
    protected static $_files = array();
    public function __construct() {}
    public static function load($loadData){
        if(isset($loadData["dir"])){ self::$_directories = $loadData["dir"];}
        if(isset($loadData["file"])){ self::$_files = $loadData["file"];}
        spl_autoload_register("Application::autoLoad");
    }
    public static function autoLoad($className){
        $directories = self::$_directories;
        $dsClassName = str_replace("_", "/", $className);
        if(is_array($directories)){
            foreach($directories as $directorie){
                $filePath = rtrim($directorie, "/") . "/" . $dsClassName . ".php";
                if(is_file($filePath)){
                    require $filePath;
                    return true;
                }
            }
        }
        $files = self::$_files;
        if(is_array($files)){
            foreach($files as $key => $file){
                if($key == $className){
                    require $file;
                    return true;
                }                
            }
        }
        throw new Exception("No found class " . $className, 500);
    }
    public static function writeLog($type, $data, $addTime = true){
        $data = $addTime ? (date("Y-m-d H:i:s") . "\t" . $data) : $data;
        file_put_contents(LOG_PATH . "/" . LOG_NAME . "_{$type}_" . date("Y-m-d") . ".log", $data . "\r\n", FILE_APPEND);
    }
    public static function display($result){
        header("Content-Type: application/json; charset=UTF-8");
        header("Cache-Control: no-store");
        header("Server-Node: " . ip2long($_SERVER["SERVER_ADDR"]));
        echo json_encode($result);
        //note 请求响应日志
        $consume = (int)round((microtime(true) - START_TIME) * 1000, 1);
        $logInfo = self::$_inputs;
        $logInfo['client_ip'] = self::getIP();
        $logInfo['logtime'] = date("Y-m-d H:i:s");
        $logInfo['consume'] = $consume;
        $logInfo['errno'] = $result['errno'];
        $logInfo['msg'] = $result['msg'];
        self::writeLog('request', json_encode($logInfo), false);
    }
    public static function get($name, $hash = 'get', $default = null) {
        $hash = strtolower ( $hash );
        switch ($hash) {
            case 'get' : $input = self::$_inputs['get'];break;
            case 'post' : $input = self::$_inputs['post'];break;
            case 'cookie' : $input = self::$_inputs['cookie'];break;
            case 'input' : $input = self::$_inputs['input'];break;
            case 'files' : $input = $_FILES;break;
            case 'server' : $input = $_SERVER;break;
            default : $input = self::$_inputs[$hash];break;
        }
        $tmp = isset ($input[$name]) ? $input[$name] : $default;
        return (is_array($tmp) ? $tmp : trim($tmp));
    }
    public static function set($name, $hash = 'get', $value = ""){
        self::$_inputs[strtolower($hash)][$name] = $value;
    }
    public static function getIP(){
        $ip = false;
        if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")){
            $ip = getenv("REMOTE_ADDR");
        }else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")){
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    public static function http($url, $data = "", $headers = array(), $timeout = 3){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if($headers){
            $headerArr = array();
            foreach($headers as $key => $value){
                $headerArr[] = $key . ':' . $value;
            }
            curl_setopt ($ch, CURLOPT_HTTPHEADER, $headerArr);
        }
        if($data){  // 自动POST数据
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            //防止 100 continue 交互
            curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Expect:' ) );
        }
        $r=curl_exec($ch);
        curl_close($ch);
        return $r;
    }
    static public function catchErrHandler($level, $msg, $file, $line){
        switch ($level){
            case E_NOTICE:
            case E_USER_NOTICE:
                $error_type = 'Notice';break;
            case E_WARNING:
            case E_USER_WARNING:
                $error_type = 'warning_error';break;
            case E_ERROR:
                $error_type = 'Fatal Error';break;
            case E_USER_ERROR:
                $error_type = 'User Fatal Error';break;
            default:
                $error_type = 'Unknown';break;
        }
        $error_msg = $error_type . ': ' . $msg . ' in '.$file.':('.$line.')';
        self::writeLog('error', $error_msg);
    }
    static public function shutdown(){
        $error = error_get_last();
        if (!empty($error)) { self::catchErrHandler($error['type'], $error['message'], $error['file'], $error['line']);}
        if(defined("CONSUME_TIME_LIMIT") && defined("START_TIME")){
            $limit = CONSUME_TIME_LIMIT;
            $consume = round(microtime(true) - START_TIME, 2);
            if ($consume > $limit){ self::writeLog('slow', "[" . self::get("method") . "] Run Time Out：{$consume}s,Exceed The System Setting {$limit}s.");}
        }
    }
    public static function init(){
        $inputs['get'] = $_GET;
        $inputs['post'] = $_POST;
        $inputs['cookie'] = $_COOKIE;
        $inputs['input'] = file_get_contents("php://input");
        self::$_inputs = $inputs;
    }
    private function Route(){
        $method = self::get('method');
        if($method){
            $info = explode(".", $method);
            if(count($info) == 2){
                $controllerName = ucfirst($info[0]) . 'Controller';
                $actionName = strtolower($info[1]) . 'Action';
                //note 实例化控制器
                $this->_controller = new $controllerName();
                if(method_exists($this->_controller, $actionName)){
                    $className = get_class($this->_controller);
                    $class = new ReflectionClass($className);
                    $this->_method = $class->getMethod($actionName);
                }else{
                    throw new Exception("No method exists", 500);
                }
            }else{
                throw new Exception("Error method param", 401);
            }
        }else{
            throw new Exception("No method param", 401);
        }
    }
    private function prepareParams(){
        $args = array();
        $params = $this->_method->getParameters();
        if($params){
            foreach($params as $p){
                $input = false;
                $pargName = $p->getName();
                $tmp = explode("_", $pargName);
                if(isset($this->_argTypes[$tmp[0]])){
                    $pargType = $this->_argTypes[$tmp[0]];
                    $pargName = substr($pargName, 2);
                }else{
                    $pargType = "get";
                }
                $input = isset( self::$_inputs[$pargType][$pargName] ) ? self::$_inputs[$pargType][$pargName] : false;
                $default = $p->isDefaultValueAvailable();
                if(false === $input && false === $default){
                    throw new Exception("No {$pargName} {$pargType} param", 401);
                }
                $args[$pargName] = false !== $input ? $input : $p->getDefaultValue();
            }
        }
        return $args;
    }
    public function Run() {
        $this->Route();
        return $this->_method->invokeArgs($this->_controller, $this->prepareParams());
    }
}