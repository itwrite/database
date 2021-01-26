<?php
/**
 * Created by PhpStorm.
 * User: itwri
 * Date: 2019/4/2
 * Time: 14:07
 */

namespace Jasmine\Database\Connection\Interfaces;


use Jasmine\Database\Connection\Capsule\Link;

interface ConnectionInterface
{

    /**
     * @param string $flag
     * @return mixed
     * itwri 2019/12/19 13:10
     */
    public function getConfig($flag = 'write');

    /**
     * @param bool $master
     * @return Link
     * itwri 2019/12/19 13:07
     */
    public function getLink($master = true);

    /**
     * @return mixed
     * itwri 2019/12/19 13:16
     */
    public function getMasterLink();

    /**
     * @return mixed
     * itwri 2019/12/19 13:16
     */
    public function getReadLink();
}