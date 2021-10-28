<?php
/**
 * Created by PhpStorm.
 * User: itwri
 * Date: 2019/4/2
 * Time: 12:55
 */

namespace Jasmine\Database;

use Jasmine\Database\Grammar\Grammar;
use Jasmine\Database\Grammar\Mysql;
use Jasmine\Database\Query\Capsule\Expression;
use Jasmine\Database\Query\From;
use Jasmine\Database\Query\Group;
use Jasmine\Database\Query\Having;
use Jasmine\Database\Query\Join;
use Jasmine\Database\Query\Limit;
use Jasmine\Database\Query\Order;
use Jasmine\Database\Query\Schema\Eloquent;
use Jasmine\Database\Query\Select;
use Jasmine\Database\Query\Set;
use Jasmine\Database\Query\Where;


use Jasmine\Database\Interfaces\BuilderInterface;

class Builder implements BuilderInterface
{
    protected $tablePrefix = '';

    protected $Select = null;
    protected $From = null;
    protected $Join = null;
    protected $Where = null;
    protected $Order = null;
    protected $Group = null;
    protected $Having = null;
    protected $Limit = null;
    protected $Set = null;

    /**
     * @var Grammar
     */
    protected $Grammar = null;

    function __construct()
    {
        $this->Select = new Select();
        $this->From = new From();
        $this->Join = new Join();

        $this->Where = new Where();
        $this->Order = new Order();
        $this->Group = new Group();

        $this->Having = new Having();
        $this->Limit = new Limit();
        $this->Set = new Set();
    }

    /**
     * @return Select|null
     */
    function &getSelect()
    {
        return $this->Select;
    }

    /**
     * @return From|null
     */
    function &getFrom()
    {
        return $this->From;
    }

    /**
     * @return Join|null
     */
    function &getJoin()
    {
        return $this->Join;
    }

    /***
     * @return Where|null
     */
    function &getWhere()
    {
        return $this->Where;
    }

    /**
     * @return Order|null
     */
    function &getOrder()
    {
        return $this->Order;
    }

    function &getGroup()
    {
        return $this->Group;
    }

    /**
     * @return Having|null
     */
    function &getHaving()
    {
        return $this->Having;
    }

    /**
     * @return Limit|null
     */
    function &getLimit()
    {
        return $this->Limit;
    }

    /**
     * @return Set|null
     */
    function &getSet()
    {
        return $this->Set;
    }

    /**
     * @param $value
     * @return Expression
     * itwri 2020/3/10 21:02
     */
    public function raw($value): Expression
    {
        return new Expression($value);
    }

    /**
     * @param $distinct
     * @return $this
     * itwri 2019/11/30 14:03
     */
    public function distinct($distinct): Builder
    {
        $this->getSelect()->distinct($distinct);
        return $this;
    }

    /**
     * @param string $fields
     * @return $this
     * itwri 2020/3/8 16:55
     */
    public function fields($fields = '*'): Builder
    {
        if ($fields instanceof \Closure) {
            $this->Select->fields(function () use ($fields) {
                return call_user_func($fields, (new self()));
            });
        } else {
            $this->Select->fields($fields);
        }
        return $this;
    }

    /**
     * @param $table
     * @param bool $append
     * @return $this
     */
    public function table($table, $append = false): Builder
    {
        $this->From->table($this->tablePrefix.$table, $append);
        return $this;
    }

    /**
     * @param $table
     * @param string $on
     * @param string $type
     * @return $this
     */
    public function join($table, $on = null, $type = ''): Builder
    {
        $arguments = func_get_args();
        if(func_num_args()>0){
            $arguments[0] = $this->tablePrefix.trim($arguments[0]);
        }
        call_user_func_array(array($this->Join, 'join'), $arguments);
        return $this;
    }

    /**
     * @param $table
     * @param string|null $on
     * @return Builder
     * itwri 2019/12/5 14:22
     */
    public function leftJoin($table, string $on = null): Builder
    {
        return $this->join($table, $on,'left');
    }

    /**
     * @param $table
     * @param null $on
     * @return Builder
     * itwri 2019/12/5 14:25
     */
    public function rightJoin($table, $on = null): Builder
    {
        return $this->join($table, $on,'left');
    }

    /**
     * @param $table
     * @param null $on
     * @return Builder
     * itwri 2019/12/5 14:26
     */
    public function innerJoin($table, $on = null): Builder
    {
        return $this->join($table, $on,'inner');
    }

    /**
     * @param $field
     * @param string $operator
     * @param string $value
     * @param string $boolean
     * @return $this
     */
    public function where($field, $operator = '', $value = '', $boolean = 'and'): Builder
    {
        call_user_func_array(array($this->Where, 'where'), func_get_args());
        return $this;
    }

    /**
     * @param $field
     * @param array $values
     * @param string $boolean
     * @return Builder
     */
    public function whereIn($field, Array $values, $boolean = 'and'): Builder
    {
        return $this->where($field, 'in', $values, $boolean);
    }

    /**
     * @param $field
     * @param array $values
     * @param string $boolean
     * @return Builder
     */
    public function whereNotIn($field, Array $values, $boolean = 'and'): Builder
    {
        return $this->where($field, 'not in', $values, $boolean);
    }

    /**
     * @param $field
     * @param array $values
     * @param string $boolean
     * @return Builder
     */
    public function whereBetween($field, Array $values, $boolean = 'and'): Builder
    {
        return $this->where($field, 'between', $values, $boolean);
    }

    /**
     * @param $field
     * @param $value
     * @param string $boolean
     * @return Builder
     */
    public function whereLike($field, $value, $boolean = 'and'): Builder
    {
        return $this->where($field, 'like', $value, $boolean);
    }

    /**
     * @param string $field
     * @return $this
     */
    public function orderBy($field = ''): Builder
    {
        $this->Order->field($field);
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function groupBy($field = ''): Builder
    {
        $this->Group->field($field);
        return $this;
    }

    /**
     * @param $field
     * @param string $operator
     * @param string $value
     * @param string $boolean
     * @return $this
     */
    public function having($field, $operator = '', $value = '', $boolean = 'and'): Builder
    {
        call_user_func_array(array($this->Having, 'having'), func_get_args());
        return $this;
    }

    /**
     * @param int $offset
     * @param int $page_size
     * @return $this
     */
    public function limit($offset = 0, $page_size = 10): Builder
    {
        if (func_num_args() == 1) {
            $this->Limit->setOffset(0)->setPageSize($offset);
        } else {
            $this->Limit->setOffset($offset)->setPageSize($page_size);
        }
        return $this;
    }

    /**
     * @param $field
     * @param string $value
     * @return $this
     */
    public function set($field, $value = ''): Builder
    {
        call_user_func_array(array($this->Set, 'set'), func_get_args());
        return $this;
    }

    /**
     * @param string $operation
     * @return $this
     */
    public function clear($operation = ''): Builder
    {
        $operations = explode(',', "select,from,join,where,order,group,having,limit,set");
        if (func_num_args() == 0) {
            $this->clear($operations);
        } elseif (is_array($operation)) {
            $operations = (array)array_values($operation);
            foreach ($operations as $operate) {
                $this->clear($operate);
            }
            return $this;
        } elseif (in_array($operation = strtolower($operation), $operations) && property_exists($this, ucfirst($operation))) {
            $this->{ucfirst($operation)}->clear();
        }
        return $this;
    }

    /**
     * @param string $options
     * @return $this
     */
    public function roll($options = ''): Builder
    {
        foreach (func_get_args() as $arg) {
            if (is_string($arg) && !empty($arg)) {
                $arr = explode(',', $arg);
                foreach ($arr as $prop) {
                    if (property_exists($this, $prop = ucfirst($prop))) {
                        $target = $this->$prop;
                        if ($target instanceof Eloquent) {
                            $target->roll();
                        }
                    }
                }
            } elseif (is_array($arg)) {
                foreach ($arg as $v) {
                    $this->roll($v);
                }
            }
        }
        return $this;
    }

    /**
     * @param Grammar $grammar
     * @return string
     */
    function toSelectSql(Grammar $grammar): string
    {
        return $grammar->toSelectSql($this);
    }

    /**
     * @param Grammar $grammar
     * @param bool $replace
     * @return string
     */
    function toInsertSql(Grammar $grammar, bool $replace=false): string
    {
        return $grammar->toInsertSql($this,$replace);
    }

    /**
     * @param Grammar $grammar
     * @return string
     */
    function toUpdateSql(Grammar $grammar): string
    {
        return $grammar->toUpdateSql($this);
    }

    /**
     * @param Grammar $grammar
     * @return string
     */
    function toDeleteSql(Grammar $grammar): string
    {
        return $grammar->toDeleteSql($this);
    }

    /**
     * @param Grammar $grammar
     * @return string
     */
    function toCountSql(Grammar $grammar): string
    {
        return $grammar->toCountSql($this);
    }
}
