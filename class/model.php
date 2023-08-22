<?php
class Model {
    static $allowedOperators = ['=','!=','>','<','>=','<=','LIKE'];

    public ?int $id = null;
    private ?string $created;
    private ?string $modified;
    
    static string $tableName;
    static $fields = ['id','created','modified'];

    public function getCreated():?DateTime {
        return $this->created;
    }
    public function getModified():?DateTime {
        return $this->modified;
    }

    public static function getAll() : array {
        $pdo = db::getPDO();
        
        $sql = "SELECT * FROM `".static::$tableName."`";
        $query = $pdo->query($sql);

        $results = $query->fetchAll(PDO::FETCH_CLASS, static::class);
        return $results;
    }

    public static function checkForId(int $id) : bool {
        $pdo = db::getPDO();

        $sql = "SELECT COUNT(id) as c FROM `".static::$tableName."` WHERE id=:id";
        $params = [
            "id" => $id,
        ];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result === false) { return null; }
        return ($result['c']>0);
    }

    public static function getById(int $id) {
        $pdo = db::getPDO();

        $sql = "SELECT * FROM `".static::$tableName."` WHERE id=:id";
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

        $sql = "SELECT * FROM `".static::$tableName."` WHERE ".implode(" AND ", $criteria_strings);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($criteria_values);

        $results = $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
        return $results;
    }

    public function save() {
        $pdo = db::getPDO();
        
        // If id is set and record exists then update; otherwise, create new
        $criteria_strings = [];
        $criteria_values = [];
        $insert_placeholders = [];

        // Loop through all properties
        foreach (static::$fields as $field) {
            $criteria_strings[] = "`{$field}` = ?";
            $criteria_values[] = $this->{$field};
            $insert_placeholders[] = '?';
        }

        if(empty($this->id) || !static::checkForId($this->id)) {
            // Create record
            $sql = "INSERT INTO `".static::$tableName."` (`".implode('`,`',static::$fields)."`) VALUES (".implode(',',$insert_placeholders).")";
        } else {
            // Update record
            $sql = "UPDATE `".static::$tableName."` SET ".implode(',',$criteria_strings)."` WHERE id=?";
            $criteria_values[] = $this->id;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($criteria_values);
    }
}