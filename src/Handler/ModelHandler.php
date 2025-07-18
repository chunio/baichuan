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

    //「DB::connection($this->model->getConnectionName())->table($this->model->getTable())」支持複用鏈接
    //protected string $connection;

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
        array $pagination = [], //example:[1/*pageIndex*/, 1000/*pageLimit*/]
        bool $buildSql = false
    )
    {
        $handler = DB::connection($this->model->getConnectionName())->table($this->model->getTable())->select(...$select); // ->where($where);
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
        if($pagination && $pagination[1] > 0) $handler->forPage($pagination[0] >= 1 ? $pagination[0] : 1, $pagination[1]);
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

    public function commonInsert(array $data, bool $insertOrIgnore = false)/*: int|bool*/
    {
        $connection = DB::connection($this->model->getConnectionName());
        $table = $connection->table($this->model->getTable());
        if(!($data[0] ?? [])){
            return $insertOrIgnore ? ($table->insertOrIgnore($data) ? $connection->getPdo()->lastInsertId()/*TODO:待驗證*/ : false) : $table->insertGetId($data);
        } else {
            return $insertOrIgnore ? $table->insertOrIgnore($data) : $table->insert($data);
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
        $handler = DB::connection($this->model->getConnectionName())->table($this->model->getTable());
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

    public function commonDelete(array $where): int
    {
        $handler = DB::connection($this->model->getConnectionName())->table($this->model->getTable());
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
        return $handler->delete();
    }

}
