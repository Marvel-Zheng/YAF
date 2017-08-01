<?php
/**
 * File: Model.php
 * Functionality: Core PDO model class
 * Author: 大眼猫
 * Date: 2013-2-28
 * Note:
 *	1 => This class requires PDO support !
 *	2 => $conn MUST BE set to static for transaction !
 */

abstract class Model {

    private static $obj;
    private static $conn;
    private   $result = NULL;
    protected $server;              //数据库服务器
    protected $database;            //数据库名称
    protected $table;               //数据库表名
    protected $pk = 'id';           //主键
    private   $options;            // SQL 中的 field, where, orderby, limit
    private   $selectOne = FALSE;  // 是否是 SelectOne, 不需要 updateOne, deleteOne
    private   $sql;

    // success code of PDO
    private $successCode = '0';

    // The result of last operation: failure OR success
    private $success = FALSE;

    // SQL log file: Log SQL error for debug if NOT under DEV
    private $logFile;

    /**
     * Constructor
     */
    function __construct() {
        $this->logFile = APP_PATH.'log/DB.log';
    }

    /**
     * Connect to MySQL [Support read/write splitting]
     *
     * @param string => use default DB if parameter is not specified !
     * @return NULL
     */
    private function connect($type = 'WRITE') {
        $config = $this->getDbConfig();
        $type = strtolower($type);
        if (!$this->server) {
            $this->server = $config['mysql']['read']['server'];
        }
        $db     = $this->database ? $this->database : $config['mysql']['read']['database_name'];
        $driver = $config['mysql']['read']['database_type'];
        $host   = $config['mysql']['read']['server'];
        $port   = $config['mysql']['read']['port'];
        $user   = $config['mysql']['read']['username'];
        $pwd    = $config['mysql']['read']['password'];
        $persistent = isset($config['mysql']['read']['pconnect']) ? $config['mysql']['read']['pconnect'] : 0;
        if($persistent){
            $option = array(PDO::ATTR_PERSISTENT => 1);
        }else{
            $option = array();
        }

        if(!$port){
            $port = 3306;
        }

        $dsn = $driver.':host='.$host.';port='.$port.';dbname='.$db;

        try{
            // 判断 READ, WRITE 是否是相同的配置, 是则用同一个链接, 不再创建连接
            $read_host = $config['database'][$this->server]['read']['host'];
            $read_port = $config['database'][$this->server]['read']['host'];

            $write_host = $config['database'][$this->server]['write']['host'];
            $write_port = $config['database'][$this->server]['write']['host'];

            if($read_host == $write_host && $read_port == $write_port){
                $sington = TRUE;
            }

            if($sington){
                if(isset(self::$obj)) {
                    if(isset(self::$obj[$this->server]['read'])) {
                        self::$obj[$this->server]['write'] = self::$obj[$this->server]['read'];
                    }else{
                        self::$obj[$this->server]['read'] = self::$obj[$this->server]['write'];
                    }

                    self::$conn = self::$obj[$this->server]['write'];
                }
            }

            // 读写要分离则创建两个连接
            if(!isset(self::$obj[$this->server][$type])) {
                self::$conn = self::$obj[$this->server][$type] = new PDO($dsn, $user, $pwd, $option);
                self::$conn->query('SET NAMES `utf8`');
                unset($db, $driver, $host, $port, $user, $pwd, $dsn);
            }
        }catch(PDOException $e){
            if(ENV == 'DEV'){
                throw new Exception($e->getMessage());
            }else{
                file_put_contents($this->logFile, $e->getMessage().PHP_EOL, FILE_APPEND);
            }
        }
}


    /**
     * Field
     */
    final public function field($field){
        if(!$field){
            return $this;
        }

        $str = '';
        if(is_array($field)){
            foreach($field as $val){
                $str .= '`'.$val.'`, ';
            }

            $this->options['field'] = substr($str, 0, strlen($str)-2); // 2:　Cos there is a BLANK
        }else{
            $this->options['field'] = $field;
        }

        unset($str, $field);
        return $this;
    }

    /**
     * Between 支持多次调用
     */
    final public function between($key, $start, $end){
        $str = '`'.$key.'` BETWEEN "'.$start.'" AND "'.$end.'"';
        if(isset($this->options['between'])){
            $this->options['between'] .= ' AND '.$str;
        }else{
            $this->options['between'] = $str;
        }

        return $this;
    }

    /**
     * OR 也支持多次调用
     * 因为 OR 为PHP 关键字, 不能用 OR 作函数名了
     */
    final public function orr(){
        $this->options['or'] = TRUE;
        return $this;
    }


    /**
     * Where 支持多次调用
     * where 有三种调用方式
     */
    final public function where($where = '', $condition = '', $value = ''){
        if(!$where || empty($where)){
            return $this;
        }
        $str = '';
        if(is_array($where)){
            // 1: $where = array('username' => 'yaf'); 这样的形式
            $total = sizeof($where);
            $i   = 1;
            $str = '';
            foreach($where as $key => $val){
                $str .= '`'.$key.'` = "'.$val.'"';
                if($i != $total){
                    $str .= ' AND ';
                }
                $i++;
            }
        }else{
            // 2: $this->Where($where, $condition, $val); 这样的形式
            // $condition 可为 =, !=, >, >=, <, <=, IN, NOT IN, LIKE, NOT LIKE
            if($condition){
                // 此时的 $where 变成了表字段
                $str .= ' `'.$where.'`'.' '.$condition.' ';

                // 是否是 IN, NOT IN, 是则值带上 (), 支持数组或字符串
                if(stripos($condition, 'IN') !== FALSE){
                    // 如果是数组, 则 implode
                    if(is_array($value)){
                        $str .= '(';
                        foreach($value as $v){
                            $str .= '"'.$v.'",';
                        }

                        // 去掉,
                        $str = substr($str, 0, -1);
                        $str .= ')';
                    }else{
                        $error = 'The value of IN MUST BE an array';
                        $trace = debug_backtrace();
                        file_put_contents($this->logFile, $error.PHP_EOL, FILE_APPEND);
                    }
                }else if(stripos($condition, 'LIKE') !== FALSE){
                    // 是否是 LIKE, NOT LIKE
                    $str .= '"%'.$value.'%"';
                }else{
                    // =, !=, >, >=, <, <= 等形式
                    $str .= '"'.$value.'"';
                }
            }else{
                // 3: $where = 'username != "yaf"'; 这样的字符串形式
                $str = $where;
            }
        }

        // 无限 WHERE
        if(isset($this->options['where'])){
            // 是否是 OR
            if(isset($this->options['or'])){
                $connector = ' OR ';
                $this->options['or'] = FALSE;
            }else{
                $connector = ' AND ';
            }

            $this->options['where'] .= $connector.$str;
        }else{
            $this->options['where'] = $str;
        }
        unset($str, $i, $total, $where, $connector);

        return $this;
    }


    /*
     * Order 支持多次调用
     */
    final public function order($order){
        if(!$order){
            return $this;
        }

        if(is_array($order)){
            $total = sizeof($order);
            $i   = 1;
            $str = '';
            foreach($order as $key => $val){
                $str .= '`'.$key.'` '.$val;
                if($i != $total){
                    $str .= ' , ';
                }
                $i++;
            }
        }else{
            $str = $order;
        }

        if(isset($this->options['order'])){
            $this->options['order'] .= ', '.$str;
        }else{
            $this->options['order'] = $str;
        }

        unset($str, $i, $total, $order);

        return $this;
    }

    /*
     * Limit
     * 可传一个或二个参数
     */
    final public function limit($start = 0, $end = ''){
        $start = (int) $start;
        $this->options['limit'] = $start;
        if($end){
            $this->options['limit'] .= ', '.$end;
        }
        unset($start, $end);
        return $this;
    }

    // Reset SQL options
    final private function _reset() {
        unset($this->options);
    }


    /**
     * Select records
     * @return records on success or FALSE on failure
     */
    final public function select(){
        $this->sql = $this->generateSQL();
        // 连接DB
        $this->connect('READ');
        $this->execute();
        $result = $this->success ? $this->fetch() : NULL;
        if (!$result) {
            return false;
        }
        if($this->selectOne == TRUE){
            $data = $result[0];
        }else{
            $data = $result;
        }

        $this->selectOne = FALSE;
        return $data;
    }


    /**
     * Select one record
     */
    final public function find(){
        $this->options['limit'] = 1;
        $this->selectOne = TRUE;
        return $this->select();
    }


    /**
     * Insert | Add a new record
     *
     * @param Array => Array('field1'=>'value1', 'field2'=>'value2', 'field3'=>'value1')
     * @return FALSE on failure or inserted_id on success
     */
    final public function insert($map = array()) {
        if (!$map || !is_array($map)) {
            return FALSE;
        } else {
            $fields = $values = array();

            foreach ($map as $key => $value) {
                $fields[] = '`' . $key . '`';
                $values[] = "'$value'";
            }

            $fieldString = implode(',', $fields);
            $valueString = implode(',', $values);

            $this->sql = 'INSERT INTO ' . $this->getTableName() . " ($fieldString) VALUES ($valueString)";

            $this->connect();
            $this->execute();
            if ($this->success) {
                return $this->getInsertId() ? $this->getInsertId() : true;
            }
            return false;
        }
    }


    /**
     * Insert | Add a list record
     *
     * @param type $data
     * @return boolean
     */
    public function multiInsert($data){
        $sql = "INSERT INTO ". $this->getTableName();
        $sqlFieldArr = array();
        $sqlValueArr = array();
        $first = TRUE;

        foreach($data as $item){
            if(!is_array($item)){
                return FALSE;
            }

            if($first){
                $sqlFieldArr = array_keys($item);

                $sqlFieldStr = implode('`,`', $sqlFieldArr);
                $first = FALSE;
            }

            $tmp = implode('\',\'', $item);
            $tmp = "('$tmp')";
            $sqlValueArr[] = $tmp;
        }

        $sqlValueStr = implode(',', $sqlValueArr);
        $sql .= "(`$sqlFieldStr`) VALUES $sqlValueStr";

        $this->sql = $sql;
        $this->execute();

        return $this->success ? $this->getInsertId() : NULL;
    }

    /**
     * Replace | Add a new record if not exit, update if exits;
     *
     * @param Array => Array('field1'=>'value1', 'field2'=>'value2', 'field3'=>'value1')
     * @return FALSE on failure or inserted_id on success
     */
    final public function replaceInto($map) {
        if (!$map || !is_array($map)) {
            return FALSE;
        } else {
            $fields = $values = array();

            foreach ($map as $key => $value) {
                $fields[] = '`' . $key . '`';
                $values[] = "'$value'";
            }

            $fieldString = implode(',', $fields);
            $valueString = implode(',', $values);

            $sql = 'REPLACE INTO ' . $this->getTableName() . " ($fieldString) VALUES ($valueString)";
            $this->sql = $sql;

            $this->execute();

            return $this->success ? $this->getInsertId() : NULL;
        }
    }


    /**
     * Execute special SELECT SQL statement
     *
     * @param string  => SQL statement for execution
     */
    final public function query($sql) {
        if($sql){
            $this->sql = $sql;
        }else{
            return NULL;
        }

        $this->connect();
        $this->execute();
        $this->checkResult();

        if($this->success){
            return $this->fetch();
        }else{
            return FALSE;
        }
    }

    // 根据ID 查询字段:
    public function selectById($field, $id){
        $where = array($this->pk => $id);
        return $this->field($field)->where($where)->selectOne();
    }

    // 根据ID更新某一条记录
    public function updateById($map, $id){
        $where = array($this->pk => $id);
        return $this->where($where)->updateOne($map);
    }

    // 根据ID删除某一条记录或多条记录
    public function deleteById($id){
        if (empty($id) || !$id) {
            return false;
        }
        if (is_array($id)) {
            return $this->where($this->pk, 'IN', $id)->delete();
        } else {
            if(!$id){
                return FALSE;
            }
            return $this->where(array($this->pk=>$id))->deleteOne();
        }
    }

    // 根据ID获取某个字段
    public function selectFieldById($field, $id){
        $where = array($this->pk => $id);
        $data = $this->field($field)->where($where)->selectOne();
        return $data[$field];
    }

    /**
     * Generate SQL by options for Select, SelectOne
     */
    final protected function generateSQL(){
        if(isset($this->options['field'])){
            $field = $this->options['field'];
        }else{
            $field = '*';
        }

        $sql = 'SELECT '. $field .' FROM `'. $this->getTableName(). '`';

        if(isset($this->options['where'])){
            $sql .= ' WHERE '. $this->options['where'];
        }

        // 是否有 BETWEEN
        if(isset($this->options['between'])){
            if(isset($this->options['where'])){
                $sql .= ' AND ';
            }else{
                $sql .= ' WHERE ';
            }

            $sql .= $this->options['between'];
        }

        if(isset($this->options['order'])){
            $sql .= ' ORDER BY '. $this->options['order'];
        }

        if(isset($this->options['limit'])){
            $sql .= ' LIMIT '. $this->options['limit'];
        }
        return $sql;
    }


    /**
     * Return last inserted_id
     *
     * @param NULL
     * @return the last inserted_id
     */
    public function getInsertId() {
        return self::$conn->lastInsertId();
    }


    /**
     * Fetch data
     */
    private function fetch() {
        return $this->result->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Calculate record counts
     *
     * @param string => where condition
     * @return int => total record counts
     */
    final public function count() {
        $data = $this->field('COUNT(*) AS `count`')->find();
        return $data['count'];
    }


    /**
     * Execute SELECT | INSERT SQL statements
     *
     * <br /> Remark:  If error occurs and UAT is TRUE, call raiseError() to display error and halt !
     * @param string => SQL statement to execute
     * @return result of execution
     */
    final private function execute() {
        $this->result = self::$conn->query($this->sql);
        $this->checkResult();
    }


    /**
     * Update record(s)
     *
     * @param array  => $map = array('field1'=>value1, 'field2'=>value2, 'field3'=>value3))
     * @param boolean $self => self field ?
     * @return FALSE on failure or affected rows on success
     */
    final public function update($map, $self = FALSE) {
        if(!$this->options['where'] && !$this->options['between']){
            return FALSE;
        }

        if (!$map) {
            return FALSE;
        } else {
            $this->sql = 'UPDATE `' . $this->getTableName() .'` SET ';
            $sets = array();
            if($self){
                foreach ($map as $key => $value) {
                    if (strpos($value, '+') !== FALSE) {
                        list($flag, $v) = explode('+', $value);
                        $sets[] = "`$key` = `$key` + '$v'";
                    } elseif (strpos($value, '-') !== FALSE) {
                        list($flag, $v) = explode('-', $value);
                        $sets[] = "`$key` = `$key` - '$v'";
                    } else {
                        $sets[] = "`$key` = '$value'";
                    }
                }
            } else {
                foreach ($map as $key => $value) {
                    $sets[] = "`$key` = '$value'";
                }
            }

            $this->sql .= implode(',', $sets). ' ';

            if(isset($this->options['where'])){
                $this->sql .= ' WHERE '.$this->options['where'];
            }

            // 是否有 BETWEEN
            if(isset($this->options['between'])){
                if(isset($this->options['where'])){
                    $this->sql .= ' AND ';
                }else{
                    $this->sql .= ' WHERE ';
                }

                $this->sql .= $this->options['between'];
            }

            if(isset($this->options['order'])){
                $this->sql .= ' ORDER BY '. $this->options['order'];
            }

            if(isset($this->options['limit'])){
                $this->sql .= ' LIMIT '.$this->options['limit'];
            }
            // echo $this->sql; die;
            $this->connect();
            return $this->exec();
        }
    }

    /*
     *  Update one record
     */
    public function updateOne($map, $self = FALSE){
        $this->options['limit'] = 1;
        return $this->Update($map, $self);
    }


    /**
     * Delete record(s)
     * @param string => where condition for deletion
     * @return FALSE on failure or affected rows on success
     */
    final public function delete() {
        if(!$this->options['where'] && !$this->options['between']){
            return FALSE;
        }

        $this->sql = 'DELETE FROM `'.$this->getTableName().'` WHERE '.$this->options['where'];

        // 是否有 BETWEEN
        if(isset($this->options['between'])){
            if(isset($this->options['where'])){
                $this->sql .= ' AND ';
            }else{
                $this->sql .= ' ';
            }

            $this->sql .= $this->options['between'];
        }

        if(isset($this->options['order'])){
            $this->sql .= ' ORDER BY '. $this->options['order'];
        }

        if(isset($this->options['limit'])){
            $this->sql .= ' LIMIT '.$this->options['limit'];
        }

        //echo $this->sql; die;
        $this->connect();
        return $this->Exec();
    }

    /**
     * Delete record(s)
     * @param string => where condition for deletion
     * @return FALSE on failure or affected rows on success
     */
    final public function deleteOne() {
        $this->options['limit'] = 1;
        return $this->delete();
    }


    /**
     * Execute UPDATE, DELETE SQL statements
     * <br />Remark:  If error occurs and UAT is TRUE, call raiseError() to display error and halt !
     *
     * @return result of execution
     */
    final private function exec() {
        $rows = self::$conn->exec($this->sql);
        $this->checkResult();
        return $rows;
    }


    private function getUnderscore($total = 10, $sub = 0) {
        $result = '';
        for($i=$sub; $i<= $total; $i++){
            $result .= '_';
        }
        return $result;
    }

    /**
     * Check result for the last execution
     *
     * @param NULL
     * @return NULL
     */
    final private function checkResult() {
        $this->_reset();
        if (self::$conn->errorCode() != $this->successCode) {
            $this->success = FALSE;
            $error = self::$conn->errorInfo();
            $traceInfo = debug_backtrace();

            if (ENV == 'DEV') {
                throw new Exception('sql error:'.$error[2].$this->sql);
                //Helper::raiseError($traceInfo, $error[2], $this->sql);
            } else {
                // Log error SQL and reason for debug
                $errorMsg = get_client_ip(). ' | ' .date('Y-m-d H:i:s') .PHP_EOL;
                $errorMsg .= 'SQL: '. $this->sql .PHP_EOL;
                $errorMsg .= 'Error: '.$error[2]. PHP_EOL;

                $title =  'LINE__________FUNCTION__________FILE______________________________________'.PHP_EOL;
                $errorMsg .= $title;

                foreach ($traceInfo as $v) {
                    $errorMsg .= $v['line'];
                    $errorMsg .= $this->getUnderscore(10, strlen($v['line']));
                    $errorMsg .= $v['function'];
                    $errorMsg .= $this->getUnderscore(20, strlen($v['function']));
                    $errorMsg .= $v['file'].PHP_EOL;
                }

                file_put_contents($this->logFile, PHP_EOL.$errorMsg, FILE_APPEND);
                return FALSE;
            }
        }else{
            $this->success = TRUE;
        }
    }


    // ********* Execute transaction ********* //
    /**
     * 开始一个事务
     *
     * @param NULL
     * @return TRUE on success or FALSE on failure
     */
    public function beginTransaction() {
        $this->connect();
        self::$conn->beginTransaction();
    }


    /**
     * 事务提交
     *
     * @param NULL
     * @return TRUE on success or FALSE on failure
     */
    public function commit() {
        self::$conn->commit();
    }


    /**
     * 事务回滚
     *
     * @param  NULL
     * @return TRUE on success or FALSE on failure
     */
    public function rollback() {
        self::$conn->rollBack();
    }

    /**
     * 获取表名
     */
    public function getTableName()
    {
        $dbConfig = $this->getDbConfig();
        if (!$this->table) {
            $className = get_class($this);
            $className = str_replace('Model', '', $className);
            $this->table = strtolower($className);
        }

        $server = $this->server ? $this->server : $dbConfig['database']['default']['server'];
        $tablePrefix = $dbConfig['database'][$server]['read']['prefix'];
        if ($tablePrefix && (substr($this->table, 0, strlen($tablePrefix)) != $tablePrefix)) {
           $this->table = $tablePrefix.$this->table;
        }
        return $this->table;
    }

    protected function getDbConfig()
    {
        return Yaf_Registry::get('config');
    }

    public function getLastSql()
    {
        return $this->sql;
    }

    // *************** End ***************** //


    /**
     * 关闭数据库连接
     *
     * @param NULL
     * @return NULL
     */
    private function close() {
        self::$conn = NULL;
    }


    /**
     * Destructor
     *
     * @param NULL
     * @return NULL
     */
    function __destruct() {
        $this->close();
    }

}