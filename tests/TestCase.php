<?php
/**
 * Created by PhpStorm.
 * User: itwri
 * Date: 2021/1/20
 * Time: 15:33
 */

namespace Jasmine\Database\Tests;


use Jasmine\Database\Database;

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
    
     public function setUp()
     {
         $this->db = new Database([
             'grammar'=>\Jasmine\library\db\grammar\Mysql::class,
             'host'=>'127.0.0.1',
             'dbname'=>'a1',
             'port'=>3306,
             'username'=>'root',
             'password'=>'root',
             'table_prefix'=>'',
             'sticky'=>true,
             'debug'=>false
         ]);
     }

    function testConnection(){
       $res = $this->db->table('ht_config')->limit(20)->select();
       print_r($res);
    }
}