<?php
namespace Tests;
use Jasmine\Database\Database;

/**
 * Created by PhpStorm.
 * User: itwri
 * Date: 2021/1/20
 * Time: 15:33
 */

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Database|null
     */
    protected $db = null;
    function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * -------------------------------------------
     * -------------------------------------------
     * @return Database|null
     * itwri 2024/2/2 17:31
     */
    public function getDb(){
        if(is_null($this->db)){
            $this->db = new Database([
                'grammar'=>\Jasmine\Database\Grammar\Mysql::class,
                'host'=>'127.0.0.1',
                'dbname'=>'dbcoord_test',
                'port'=>3306,
                'username'=>'root',
                'password'=>'123456',
                'table_prefix'=>'',
                'sticky'=>true,
                'debug'=>false
            ]);
        }
        return $this->db;
    }

    /**
     * -------------------------------------------
     * -------------------------------------------
     * itwri 2024/2/2 17:31
     */
    function testConnection(){
        $res = $this->getDb()->table('users')->limit(20)->select();
        print_r($res);
    }
}
