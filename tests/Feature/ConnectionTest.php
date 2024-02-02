<?php


namespace Tests\Feature;

use Tests\TestCase;

class ConnectionTest extends TestCase
{
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
