<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Hyperf\Di\Annotation\Inject;
use Hyperf\GoTask\MongoClient\MongoClient;
use function Hyperf\Config\config;

//TODO:待調試
class MongoDBHandler
{

    /**
     * @var string
     * db.tupop_stat.createIndex({"payment_datetime":-1})//創建倒序索引
     * db.tupop_stat.createIndex({"coin_status":"hashed"})//創建哈希索引
     */

    public $db = '';

    public $collection = '';

    public $commonField = [
        'delete_time',
        'update_time',
        'create_time',
    ];

    public static $operator = [
        'IN' => '$in',
        'NIN' => '$nin',
        '>' => '$gt',
        '>=' => '$gte',
        '<' => '$lt',
        '<=' => '$lte',
        '!=' => '$ne',
        '=' => '$eq'//使用於$match
    ];

    #[Inject]
    public MongoClient $MongoClient;

    public function __construct(string $collection, string $db = '')//
    {
        $this->db = $db ?: config('mongodb.db');
        $this->collection = $collection;
        //$this->MongoClient->database()->collection($collection);
    }

    public function one(array $where, array $select = [], array $group = [], array $order = []): array
    {
        //format option[START]
        $where = self::formatWhere($where);
        $option = self::formatOption($select, $order);
        return $this->MongoClient->database($this->db)->collection($this->collection)->findOne($where, $option);
    }

    /**
     * @param array $where example:[['field1', '=', 'value1'], ['field2', '<=', 'value2'], ['field2', '>=', 'value3'], ['field3', 'IN', 'value4List']]
     * @param array $select example:['field1', 'field2', ...]
     * @param array $order example:['field1' => 'ASC', 'field2' => 'DESC', ...]
     * @return array
     * author : zengweitao@gmail.com
     * datetime: 2023/02/23 14:13
     * memo : $where僅支持邏輯與
     */
    public function commonList(array $where, array $select = [], array $group = [], array $order = [], array $limit = []): array
    {
        $where = self::formatWhere($where);
        $option = self::formatOption($select, $order);
        return $this->MongoClient->database($this->db)->collection($this->collection)->find($where, $option);
    }

    public function aggregateList(array $where, array $select = [], array $group = [], array $order = [], array $limit = [/*[$skip, ]$limit*/]): array
    {
        //管道操作符：$match，$project，$group，$sort，$limit，$skip，$unwind，$sum，$lookup，...
        $pipeline/*管道*/ = $project = $formatGroup = $groupIndex = [];
        if($where) $pipeline[]['$match'] = self::formatWhere($where);
        if($select){
            $project['_id'] = 0; //默認：返回{$_id}
            foreach ($select as $field) {
                if (strpos($field, ' AS ') !== false) {
                    //存在别名
                    list($originalField, $alias) = explode(' AS ', $field);
                    $project[$alias] = '$' . trim($originalField);
                } else {
                    //不帶別名
                    $project[trim($field)] = 1;//1表示返回
                }
            }
            $pipeline[]['$project'] = $project;
        }
        if($group){
            foreach ($group as $field){
                $groupIndex[$field] = "\${$field}";
            }
            $formatGroup = [
                //example: '_id' => ['filed1' => '$filed1', 'filed2' => '$filed2']
                '_id' => $groupIndex,
                'count' => ['$sum' => 1],
            ];
        }
        if($order) {//TODO:1$order目前僅支持作用一個字段（但使用數組入參目的是預留後續兼容多個字段）
            foreach ($order as $unitField => $unitSequence){
                $formatGroup[$unitField] = [
                    ($unitSequence === 'ASC') ? '$min'/*正序*/ : '$max'/*倒敘*/ => "\${$unitField}"
                ];
            }
        }
        $pipeline[]['$group'] = $formatGroup;
        if($limit) {
            if(count($limit) === 1) array_unshift($limit, 0);
            $pipeline[]['$skip'] = $limit[0];
            $pipeline[]['$limit'] = $limit[1];
        }
        return $this->MongoClient->database($this->db)->collection($this->collection)->aggregate(array_values($pipeline));
    }

    public function commonInsert(array $data)/*: ?ObjectId|array*/
    {
        if(!($data[0] ?? [])){
            $InsertOneResult = $this->MongoClient->database($this->db)->collection($this->collection)->insertOne($data);
            return $InsertOneResult->getInsertedId();
        }else{
            $InsertManyResult = $this->MongoClient->database($this->db)->collection($this->collection)->insertMany($data);
            return $InsertManyResult->getInsertedIDs();
        }
    }

    /**
     * @param array $where
     * @param array $data
     * @return int 返回改變行數
     * author : zengweitao@gmail.com
     * datetime: 2023/02/23 13:48
     * memo : 返回改變行數
     */
    public function commonUpdate(array $where, array $data): int
    {
        $where = self::formatWhere($where);
        $update = ['$set' => $data];
        $UpdateResult = $this->MongoClient->database($this->db)->collection($this->collection)->updateMany($where, $update);
        return $UpdateResult->getModifiedCount();
    }

    public function commonDelete($where): int
    {
        $where = self::formatWhere($where);
        $DeleteResult = $this->MongoClient->database($this->db)->collection($this->collection)->deleteMany($where);
        return $DeleteResult->getDeletedCount();
    }

    public function commonCount($where): int
    {
        $where = self::formatWhere($where);
        return $this->MongoClient->database($this->db)->collection($this->collection)->countDocuments($where);
    }

    /**
     * @param array $where []表示全部
     * @return array
     * author : zengweitao@gmail.com
     * datetime: 2023/03/15 10:22
     * memo : null
     */
    public static function formatWhere(array $where): array
    {
        $formatWhere = [];
        foreach ($where as $value){
            [$unitField, $unitOperator, $unitValue] = $value;
            if($mongoOperation = (self::$operator[$unitOperator] ?? '')){
                $formatWhere[$unitField][$mongoOperation] = $unitValue;
            }else{
                $formatWhere[$unitField] = $unitValue;
            }
        }
        return $formatWhere;
    }

    public static function formatOption(array $select, array $order): array
    {
        $option = [];
        if($select){
            $option['projection']['_id'] = 0;//默認：返回{$_id}
            foreach ($select as $unitField){
                $option['projection'/*聲明需返回的字段*/][$unitField] = 1;//1表示返回
            }
        }
        if($order){
            foreach ($order as $unitField => $unitSequence){
                $option['$sort'][$unitField] = ($unitSequence === 'ASC') ? 1/*正序*/ : -1/*倒敘*/;
            }
        }
        return $option;
    }

}