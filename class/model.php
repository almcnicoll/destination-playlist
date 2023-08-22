<?php
class Model {
    public int $id;
    private ?string $created;
    private ?string $modified;
    
    static string $tableName;

    static $allowedOperators = ['=','!=','>','<','>=','<=','LIKE'];

    public function getCreated():?DateTime {
        return $this->created;
    }
    public function getModified():?DateTime {
        return $this->modified;
    }

    public static function getAll() : array {
        $pdo = db::getPDO();
        
        $sql = "SELECT * FROM `".AuthMethod::$tableName."`";
        $query = $pdo->query($sql);

        $results = $query->fetchAll(PDO::FETCH_CLASS, static::class);
        return $results;
    }

    public static function getById(int $id) : AuthMethod {
        $pdo = db::getPDO();

        $sql = "SELECT * FROM `".AuthMethod::$tableName."` WHERE id=:id";
        $params = [
            "id" => $id,
        ];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_CLASS,static::class);
        if ($result === false) { return null; }
        return $result;
    }

    public static function find($criteria) : array {
        $pdo = db::getPDO();

        // Check arguments
        if (!is_array($criteria)) {
            throw new Exception("Find method requires an array of three-element arrays to operate");
        }
        $criteria_strings = [];
        $criteria_values = [];
        foreach ($criteria as $criterion) {
            if ( (!is_array($criterion)) || count($criterion)!=3) {
                throw new Exception("Find method requires an array of three-element arrays to operate");
            }
            // Format - field, operator, value
            list($field,$operator,$value) = $criterion;
            
            if (strpos($field,'`')!==false) {
                throw new Exception("Field names in criteria cannot contain backticks (`)");
            }
            if (!in_array($operator,Model::$allowedOperators)) {
                throw new Exception("Operator {$operator} is not allowed");
            }

            $criteria_strings[] = "`{$field}` {$operator} ?";
            $criteria_values[] = $value;
        }

        $sql = "SELECT * FROM `".AuthMethod::$tableName."` WHERE ".implode(" AND ", $criteria_strings);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($criteria_values);

        $results = $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
        return $results;
    }
}