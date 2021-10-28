<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2018/4/24
 * Time: 14:50
 */

namespace Jasmine\Database\Query;


use Jasmine\Database\Query\Schema\Eloquent;

class Limit extends Eloquent{

    /**
     * @param $offset
     * @return $this
     */
    function setOffset($offset): Limit
    {
        $offset = intval($offset);
        $this->data[0] = $offset < 0 ? 0 : $offset;
        return $this;
    }

    /**
     * @param int $page_size
     * @return $this
     */
    function setPageSize(int $page_size = 0): Limit
    {
        !empty($this->data) && $this->data[1] = $page_size;
        return $this;
    }

    /**
     * @return $this
     */
    function clear(): Limit
    {
        if (count(array_keys($this->data))>0) {
            $this->cache[] = $this->data;
        }
        $this->data = array();
        return $this;
    }
}
