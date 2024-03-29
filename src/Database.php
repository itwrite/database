<?php
/**
 * Created by PhpStorm.
 * User: itwri
 * Date: 2019/4/2
 * Time: 12:47
 */

namespace Jasmine\Database;


use ErrorException;
use Jasmine\Database\Connection\Capsule\Link;
use Jasmine\Database\Connection\Connection;
use Jasmine\Database\Interfaces\DatabaseInterface;
use Jasmine\Database\Query\Capsule\Expression;


class Database extends Builder implements DatabaseInterface
{
    protected $debug = false;
    /**
     * @var Connection|null
     */
    protected $Connection = null;

    /**
     * @var string
     */
    protected $linkName = null;

    /**
     * @var bool
     */
    private $_sticky = false;

    /**
     * @var array
     */
    protected $errorArr = [];

    protected $Logger = null;


    function __construct(array $config, $logger = null)
    {
        parent::__construct();

        $this->Logger = $logger;

        $this->tablePrefix = $config['table_prefix'] ?? '';
        $this->Connection = new Connection($config);
    }

    /**
     * @param bool $debug
     * @return $this
     */
    public function debug(bool $debug = false): Database
    {
        $this->debug = $debug == true;
        return $this;
    }

    /**
     * @param $sticky
     * @return $this
     * itwri 2019/12/19 14:33
     */
    public function sticky($sticky): Database
    {
        $this->_sticky = $sticky == true;
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function link($name): Database
    {
        $this->linkName = $name;
        return $this;
    }

    /**
     * @param null $name
     * @return Link|mixed|null
     * @throws \Exception
     * itwri 2019/12/19 14:18
     */
    protected function getLink($name = null)
    {
        /**
         * 如果为真，使用主连接
         */
        if ($this->_sticky == true) {
            return $this->Connection->getMasterLink();
        }

        if (!is_null($name)) {
            return $this->Connection->getLink($name);
        }

        return $this->Connection->getLink($this->linkName);
    }

    /**
     * @param array $data
     * @param bool $is_replace
     * @return int
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    public function insert(Array $data = [], bool $is_replace = false): int
    {
        $Link = $this->getLink(true);
        //set data
        $this->set($data);
        //get the insert sql
        $SQL = $this->toInsertSql($Link->getGrammar(), $is_replace);

        //execute the sql
        $this->exec($SQL);
        //get the inserted Id
        $lastInsertId = $Link->getPdo()->lastInsertId();

        return intval($lastInsertId);
    }

    /**
     * 批量插入
     * @param array $data
     * @param int $size
     * @param bool $is_replace
     * @return bool
     * @throws \Exception
     * itwri 2019/12/30 10:27
     */
    public function insertAll(Array $data, $size = 1000, $is_replace = false)
    {

        /**
         * 处理数据
         */
        $insertData = [];
        $tempData = [];
        foreach ($data as $key => $datum) {
            if (is_string($key) && (is_string($datum) || is_numeric($datum) || $datum instanceof Expression)) {
                $insertData[$key] = $datum;
            }

            if (is_array($datum)) {
                $tempKey = implode('-', array_keys($datum));
                if (!isset($tempData[$tempKey])) {
                    $tempData[$tempKey] = [];
                }
                $tempData[$tempKey][] = $datum;
            }
        }

        $k = implode('-', array_keys($insertData));
        if (isset($tempData[$k])) {
            $tempData[$k][] = $insertData;
        }

        return $this->transaction(function () use ($tempData, $size, $is_replace) {
            $Link = $this->getLink(true);

            foreach ($tempData as $tempDatum) {
                $count = count($tempDatum);
                $newData = [];
                $successCount = 0;
                foreach ($tempDatum as $key => $datum) {
                    $newData[] = $datum;

                    if (count($newData) >= $size) {

                        //set data
                        $this->set($newData);
                        //get the insert sql
                        $SQL = $this->toInsertSql($Link->getGrammar(), $is_replace);

                        //execute the sql
                        $num = $this->exec($SQL);
                        $successCount += (int)$num;

                        //已执行则重置
                        $newData = [];

                        /**
                         * 这里要把from回滚，保证后面的执行一致
                         */
                        $this->getFrom()->roll();
                    }
                }

                //set data
                $this->set($newData);
                //get the insert sql
                $SQL = $this->toInsertSql($Link->getGrammar(), $is_replace);

                //execute the sql
                $num = $this->exec($SQL);
                $successCount += (int)$num;

                //
                if ($successCount != $count) {
                    throw new \Exception('Error with the data.');
                }
            }
            return true;
        });
    }

    /**
     * @param array $data
     * @return bool|int
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    public function update(array $data = [])
    {
        if (!empty($data)) {
            //set data
            $this->set($data);
        }

        //get the update sql;
        $SQL = $this->toUpdateSql($this->getLink(true)->getGrammar());

        return $this->exec($SQL);
    }

    /**
     * @return bool|int
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    public function delete()
    {
        //get the delete sql
        $SQL = $this->toDeleteSql($this->getLink(true)->getGrammar());

        return $this->exec($SQL);
    }

    /**
     * @return int
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    public function count(): int
    {
        $this->limit(1);
        //get the select sql
        $SQL = $this->toCountSql($this->getLink()->getGrammar());

        //query
        $st = $this->query($SQL);

        if ($st !== false) {
            return intval($st->fetch(\PDO::FETCH_ASSOC)['__COUNT__']);
        }
        return 0;
    }

    /**
     * @param string $fields
     * @param int $fetch_type
     * @return bool|mixed
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    public function get($fields = '*', $fetch_type = \PDO::FETCH_ASSOC)
    {
        parent::fields($fields);

        $this->limit(1);
        //get the select sql
        $SQL = $this->toSelectSql($this->getLink()->getGrammar());
        //query
        $st = $this->query($SQL);
        if ($st !== false) {
            //return the result
            return $st->fetch($fetch_type);
        }
        return false;
    }

    /**
     * @param string $fields
     * @param int $fetch_type
     * @return bool|mixed
     * itwri 2019/12/19 14:29
     */
    public function first(string $fields = '*', int $fetch_type = \PDO::FETCH_ASSOC)
    {
        return call_user_func_array([$this, 'get'], [$fields, $fetch_type]);
    }

    /**
     * @param null $fields
     * @param int $fetch_type
     * @return array
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    public function getAll($fields = null, int $fetch_type = \PDO::FETCH_ASSOC): array
    {
        parent::fields($fields);

        //get the select sql
        $SQL = $this->toSelectSql($this->getLink()->getGrammar());

        //query
        $st = $this->query($SQL);
        if ($st) {
            return $st->fetchAll($fetch_type);
        }
        return array();
    }

    /**
     * @param string $fields
     * @param int $fetch_type
     * @return mixed
     */
    function select(string $fields = '*', int $fetch_type = \PDO::FETCH_ASSOC)
    {
        return call_user_func_array([$this, 'getAll'], func_get_args());
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    function paginator(int $page = 1, int $pageSize = 10): array
    {

        $page = $page < 1 ? 1 : $page;
        $offset = ($page - 1) * $pageSize;

        if (func_num_args() < 2) {
            $limit = $this->Limit->data();
            isset($limit[1]) && $pageSize = $limit[1] > 0 ? $limit[1] : $pageSize;
        }

        $this->limit($offset, $pageSize);

        $SQL = $this->toCountSql($this->getLink()->getGrammar());

        $total = 0;
        $st = $this->query($SQL);
        if ($st !== false) {
            $total = intval($st->fetch(\PDO::FETCH_ASSOC)['__COUNT__']);
        }

        $totalPage = ceil($total / $pageSize);

        $this->roll('select,from,join,where,group,having,order,limit');
        $list = $this->select();


        return ['total' => $total, 'items' => $list, 'pages_count' => $totalPage, 'page' => $page];
    }

    /**
     * @param $statement
     * @return bool|\PDOStatement
     */
    public function query($statement)
    {
        $res = false;
        $this->trace(function () use ($statement, &$res) {

            $time_arr = explode(' ', microtime(false));
            $start_time = $time_arr[0] + $time_arr[1];

            /**
             * =====================================================
             */
            $error_info = [];
            if ($this->isWriteAction($statement)) {
                $res = $this->getLink(true)->getPdo()->query($statement);
                $res === false && $error_info = $this->getLink(true)->getPdo()->errorInfo();
            } else {
                $res = $this->getLink()->getPdo()->query($statement);
                $res === false && $error_info = $this->getLink()->getPdo()->errorInfo();
            }
            /**
             * =====================================================
             */

            $time_arr = explode(' ', microtime(false));
            $end_time = $time_arr[0] + $time_arr[1];

            $runtime = number_format($end_time - $start_time, 10);

            $log_info = sprintf("SQL: %s %s", $statement, ($res != false ? '[true' : '[false') . ",Runtime:{$runtime}]");

            $this->log($log_info);

            //
            !empty($error_info) && $this->errorArr[] = $error_info;
            if ($res == false) {
                $this->log($this->getErrorInfo(), [], 'error');
            }

            //重置
            $this->linkName = null;

            $this->cacheSql($statement);


            if ($this->debug) {
                print_r($log_info);
                !empty($error_info) && print_r(sprintf("SQL Error: %s\r\n", var_export($error_info, true)));
            }
        });
        return $res;
    }

    /**
     * @param $statement
     * @return bool|int
     */
    public function exec($statement)
    {

        $res = false;
        $this->trace(function () use ($statement, &$res) {

            $time_arr = explode(' ', microtime(false));
            $start_time = $time_arr[0] + $time_arr[1];

            $error_info = [];
            if ($this->isWriteAction($statement)) {
                $res = $this->getLink(true)->getPdo()->exec($statement);
                $res === false && $error_info = $this->getLink(true)->getPdo()->errorInfo();
            } else {
                $res = $this->getLink()->getPdo()->exec($statement);
                $res === false && $error_info = $this->getLink()->getPdo()->errorInfo();
            }

            $time_arr = explode(' ', microtime(false));
            $end_time = $time_arr[0] + $time_arr[1];

            $runtime = number_format($end_time - $start_time, 10);

            $log_info = sprintf("SQL: %s %s", $statement, ($res != false ? '[true' : '[false') . ",Runtime:{$runtime}]" . var_export($res, true));
            $this->log($log_info);
            //
            !empty($error_info) && $this->errorArr[] = $error_info;

            if ($res == false) {
                $this->log($this->getErrorInfo(), [], 'error');
            }

            $this->linkName = null;

            $this->cacheSql($statement);


            if ($this->debug) {
                print_r($log_info);
                !empty($error_info) && print_r(sprintf("SQL Error: %s\r\n", var_export($error_info, true)));
            }
        });
        return $res;
    }

    /**
     * @param $closure
     * @return $this
     * itwri 2019/12/2 16:33
     */
    public function masterHandle($closure): Database
    {
        $this->_sticky = true;
        if (is_callable($closure)) {
            call_user_func_array($closure, [$this]);
        }
        $this->_sticky = false;
        return $this;
    }

    /**
     * @param $statement
     * @return bool
     */
    protected function isWriteAction($statement): bool
    {
        $statement = strtolower(trim($statement));
        if (strpos($statement, 'insert') !== false
            || strpos($statement, 'delete') !== false
            || strpos($statement, 'update ') !== false
            || strpos($statement, 'replace') !== false
            || strpos($statement, 'truncate') !== false
            || strpos($statement, 'create') !== false
        ) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    function startTrans(): bool
    {
        if (!$this->getLink()->getPdo()->inTransaction()) {
            return $this->getLink()->getPdo()->beginTransaction();
        }
        return true;
    }

    /**
     * @return bool
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    function commit(): bool
    {
        if ($this->getLink()->getPdo()->inTransaction()) {
            return $this->getLink()->getPdo()->commit();
        }
        return false;
    }


    /**
     * @return bool
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    public function rollback(): bool
    {
        if ($this->getLink()->getPdo()->inTransaction()) {
            return $this->getLink()->getPdo()->rollBack();
        }
        return false;
    }

    /**
     * @param \Closure $closure
     * @return bool|string
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    public function transaction(\Closure $closure)
    {
        $this->startTrans();
        try {
            $this->_sticky = true;
            $res = call_user_func_array($closure, [$this]);
            if ($res === false) {
                throw new ErrorException('User Abort.');
            }
            $this->commit();
            $this->_sticky = false;
            return true;
        } catch (\Exception $exception) {
            $this->_sticky = false;
            $this->rollback();

            /**
             * 记录日志
             */
            $this->log((string)$exception);

            /**
             * 暂存错误信息
             */
            $this->errorArr[] = $exception->getMessage();

            /**
             * 继续向外抛
             */
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param $callback
     * @return $this
     */
    public function trace($callback): Database
    {
        if ($callback instanceof \Closure) {
            /**
             * do something
             */
            call_user_func_array($callback, array());
        }
        return $this;
    }

    /**
     * @var array
     */
    protected $logSQLs = array();

    /**
     * pop out the last one SQL;
     * @return string
     */
    public function getLastSql(): string
    {
        $SQL = $this->logSQLs[count($this->logSQLs) - 1];
        return $SQL ? $SQL : "";
    }

    /**
     * @param string $sql
     * @return $this
     */
    protected function cacheSql($sql): Database
    {
        $this->logSQLs[] = $sql;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getErrorInfo()
    {
        if (count($this->errorArr) > 0) {
            return $this->errorArr[count($this->errorArr) - 1];
        }
        return null;
    }

    /**
     * @param $field
     * @param int $inc
     * @return bool|int
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    public function setInc($field, int $inc = 1)
    {
        return $this->set($field, new Expression($field . "+" . $inc))->update();
    }

    /**
     * @param $field
     * @param int $inc
     * @return bool|int
     * @throws \Exception
     * itwri 2019/12/19 13:59
     */
    public function setDec($field, int $inc = 1)
    {
        return $this->set($field, new Expression($field . "-" . $inc))->update();
    }

    /**
     * @param $message
     * @param array $context
     * @param $level
     * itwri 2020/2/26 23:22
     */
    public function log($message, array $context = array(), $level = 'info')
    {
        if ($this->getLogger() != null && method_exists($this->getLogger(), 'write')) {
            call_user_func_array([$this->getLogger(), 'write'], [$level, $message, $context]);
        }
    }

    /**
     * @return mixed|null
     * itwri 2020/7/7 11:04
     */
    public function getLogger()
    {
        return $this->Logger;
    }

    /**
     * @param $logger
     * @return $this|mixed
     * itwri 2020/7/7 11:04
     */
    public function setLogger($logger)
    {
        $this->Logger = $logger;
        return $this;
    }
}
