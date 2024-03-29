<?php
/**
 * Created by PhpStorm.
 * User: itwri
 * Date: 2019/4/2
 * Time: 14:03
 */

namespace Jasmine\Database\Connection\Capsule;

use Jasmine\Database\Grammar\Grammar;

class Link
{

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var null
     */
    protected $pdo = null;

    /**
     * @var Grammar|null
     */
    protected $Grammar = null;


    function __construct(array $config)
    {
        $this->setConfig($config);

        $grammar_class= $this->config['grammar'] ?? Grammar::class;
        $this->Grammar = new $grammar_class();
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/28
     * Time: 10:27
     *
     * @return array
     */
    function getConfig(): array
    {
        return $this->config;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/28
     * Time: 10:27
     *
     * @param array $config
     * @return $this
     */
    function setConfig(array $config): Link
    {
        $this->config = is_array($config) ? $config : [];

        if (isset($this->config['host'])) {
            $host = is_string($this->config['host']) ? $this->config['host'] : (is_array($this->config['host']) ? ($this->config['host'][rand(0, count($this->config['host']) - 1)]) : '');
            $arr = explode(':', $host);
            $this->config['host'] = $arr[0];
            $this->config['port'] = isset($arr[1]) ? $arr[1] : (isset($this->config['port']) ? $this->config['port'] : 3306);

        }

        $this->config['driver'] = isset($this->config['driver']) ? $this->config['driver'] : 'mysql';
        return $this;
    }

    /**
     * @return mixed|string
     */
    function getDbName()
    {
        return isset($this->config['dbname']) ? $this->config['dbname'] : '';
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/27
     * Time: 1:37
     *
     * @return null|\PDO
     */
    function getPdo()
    {
        if (!$this->pdo instanceof \PDO) {
            $username = isset($this->config['username']) ? $this->config['username'] : '';
            $password = isset($this->config['password']) ? $this->config['password'] : '';
            $options = isset($this->config['options']) ? $this->config['options'] : [];
            $this->pdo = new \PDO($this->parseDsn($this->config), $username, $password, $options);
            if(isset($this->config['charset'])){
                $this->pdo->exec("set names '{$this->config['charset']}'");
            }else{
                $this->pdo->exec("set names 'utf8'");
            }
        }

        return $this->pdo;
    }


    /**
     *
     * User: Peter
     * Date: 2019/3/28
     * Time: 10:29
     *
     * @return Grammar|null
     */
    function getGrammar()
    {
        return $this->Grammar;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/28
     * Time: 10:29
     *
     * @param Grammar $grammar
     */
    function setGrammar(Grammar $grammar)
    {
        $this->Grammar = $grammar;
    }

    /**
     *
     * User: Peter
     * Date: 2019/3/27
     * Time: 1:44
     *
     * @param array $config
     * @return string
     */
    protected function parseDsn(array $config = []): string
    {
        if (isset($config['dsn'])) {
            return $config['dsn'];
        }

        $arr = [];
        foreach ($config as $key => $value) {
            if (in_array($key, ['host', 'port', 'dbname', 'charset']) && (is_string($value) || is_numeric($value))) {
                array_push($arr, "{$key}={$value}");
            }
        }
        return "{$config['driver']}:" . implode(';', $arr);
    }
}
