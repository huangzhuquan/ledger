<?php

// +----------------------------------------------------------------------
// | 台账管理系统-Ledger
// +----------------------------------------------------------------------
// | 版权所有 2018~2022
// +----------------------------------------------------------------------
// | 官方网站: http://localhost
// +----------------------------------------------------------------------
// | huachun.xiang@qslb
// +----------------------------------------------------------------------
// | QQ:15516026
// +----------------------------------------------------------------------

namespace service;

use think\Db;

/**
 * 销售方式
 * Class RegionService
 */
class SalesModeService
{
    public static $table = 'SalesMode';

    /**
     * 销售方式列表
     * @param $regionCode
     */
    public static function salesModes(){
        return Db::name(self::$table)
            ->field('id,sales_mode_name')
            ->select();
    }

}