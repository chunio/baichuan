<?php

declare(strict_types=1);

/**
 * This file is part of Hyperf.
 *
 * @see     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Baichuan\Library\Handler;

use Hyperf\DbConnection\Db;

class ModelHandler //extends \Hyperf\DbConnection\Model\Model
{

    public static $DELETED_STATUS = [
        'NOT_DELETE' => null, // 未刪除
        'DELETED' => 1, // 已刪除
    ];

    public static $querier = [
        'IN' => 'whereIn',
        'NIN' => 'whereNotIn'
    ]; // 聲明查詢器

    public \Hyperf\DbConnection\Model\Model $model;

    public function __construct(string $model)
    {
        $this->model = new $model();
    }

    //return/example : Array([0] => stdClass Object([id] => 1,...))
    public function one(array $where, array $select = ['*'], array $group = [], array $order = [])
    {
        $result = $this->commonList($where, $select, $group, $order, 1);
        return $result[0] ?? [];
    }

    public function commonList(
        array $where, //example:[['field1', '=', 'value1'], ['field2', '<=', 'value2'], ['field2', '>=', 'value3'], ['field3', 'IN', 'value4List']]
        array $select = ['*'], //example:['field1', 'field2', ...]
        array $group = [],
        array $order = [], //example:['field1' => 'ASC', 'field2' => 'DESC', ...]
        int $limit = 0,
        bool $buildSql = false
    )
    {
        $handler = DB::table($this->model->getTable())->select(...$select); // ->where($where);
        foreach ($where as &$value){
            [$unitField, $unitOperator, $unitValue] = $value;
            $function = self::$querier[$unitOperator] ?? 'where';
            switch ($function) {
                case 'where':
                    $whereCondition[] = $value;
                    break;
                case 'whereIn':
                case 'whereNotIn':
                    $handler = $handler->{$function}(...[$unitField, $unitValue]);
                    break;
            }
        }
        if(isset($whereCondition)) $handler->where($whereCondition);
        if($limit) $handler->limit($limit);//DEBUG_LABEL
        if($group) $handler->groupBy(...$group);
        if($order) {
            foreach ($order as $unitField => $unitSequence){
                $handler->orderBy($unitField, $unitSequence);
            }
        }
        if ($buildSql) {
            return $handler->toSql();
        }else{
            return $handler->get()->toArray();
        }
    }

    public function commonInsert(array $data)/*: int|bool*/
    {
        if(!($data[0] ?? [])){
            return DB::table($this->model->getTable())->insertGetId($data);
        }else{
            return DB::table($this->model->getTable())->insert($data);
        }
    }

    /**
     * @param array $where
     * @param array $data
     * @return int
     * author : zengweitao@gmail.com
     * datetime: 2023/03/02 22:20
     * memo : 返回改變行數
     */
    public function commonUpdate(array $where, array $data): int
    {
        $handler = DB::table($this->model->getTable());
        foreach ($where as &$value){
            [$unitField, $unitOperator, $unitValue] = $value;
            $function = self::$querier[$unitOperator] ?? 'where';
            switch ($function) {
                case 'where':
                    $whereCondition[] = $value;
                    break;
                case 'whereIn':
                case 'whereNotIn':
                    $handler = $handler->{$function}(...[$unitField, $unitValue]);
                    break;
            }
        }
        if(isset($whereCondition)) $handler->where($whereCondition);
        return $handler->update($data);
    }

}
