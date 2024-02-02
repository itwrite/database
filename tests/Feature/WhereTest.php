<?php


namespace Tests\Feature;


use Jasmine\Database\Grammar\Mysql;
use Jasmine\Database\Query\Capsule\Expression;
use Jasmine\Database\Query\Where;
use Tests\TestCase;

class WhereTest extends TestCase
{
    public function testWhereSql(){

        $Where = new Where();
        $Where->where('machine_rooms.`level`','=',new Expression('a+1'))
            ->where('machine_rooms.`type`','not like','动力%');

        $grammar = new Mysql();
        echo $grammar->compileWhere($Where);
    }
}
