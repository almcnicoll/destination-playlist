<?php
class Model {
    static $allowedOperators = ['=','!=','>','<','>=','<=','LIKE'];

    public ?int $id = null;
    public ?string $created = null;
    public ?string $modified = null;
    
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

        $query->setFetchMode(PDO::FETCH_CLASS, static::class);
        $results = $query->fetchAll();
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

        $stmt->setFetchMode(PDO::FETCH_CLASS,static::class);
        $result = $stmt->fetch();
        if ($result === false) { return null; }
        return $result;
    }

    public static function findFirst($criteria) {
        // TODO - would be better (more efficient in db) to run query with LIMIT 1 instead of this
        $values = static::find($criteria);
        if (count($values)==0) { return null; }
        return $values[0];
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

        $stmt->setFetchMode(PDO::FETCH_CLASS, static::class);
        $results = $stmt->fetchAll();
        return $results;
    }

    public function save() : ?int {
        $pdo = db::getPDO();

        // If id is set and record exists then update; otherwise, create new
        $criteria_strings = [];
        $criteria_values = [];
        $insert_placeholders = [];

        $is_insert = ( (empty($this->id) || !static::checkForId($this->id)) );

        // Loop through all properties
        foreach (static::$fields as $field) {
            $criteria_strings[] = "`{$field}` = ?";
            //echo "`{$field}` = ".$this->{$field}."\n";
            if ($field == 'created' && $is_insert) {
                $criteria_values[] = date('Y-m-d H:i:s');
            } elseif ($field == 'modified') {
                $criteria_values[] = date('Y-m-d H:i:s');
            } else {
                $criteria_values[] = $this->{$field};
            }
            $insert_placeholders[] = '?';
        }

        if ($is_insert) {
            // Create record
            $sql = "INSERT INTO `".static::$tableName."` (`".implode('`,`',static::$fields)."`) VALUES (".implode(',',$insert_placeholders).")";
        } else {
            // Update record
            $sql = "UPDATE `".static::$tableName."` SET ".implode(',',$criteria_strings)." WHERE id=?";
            $criteria_values[] = $this->id;
        }
        //echo "{$sql}\n";
        //print_r($criteria_values);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($criteria_values);

        if ($is_insert) {
            $this->id = $pdo->lastInsertId();
        }
        return $this->id;
    }

    public function delete() : bool {
        $pdo = db::getPDO();

        $is_new = ( (empty($this->id) || !static::checkForId($this->id)) );

        if ($is_new) {
            throw new Exception("Could not delete: no matching record in database");
        }

        $sql = "DELETE FROM `".static::$tableName."` WHERE id=:id";
        $params = [
            "id" => $this->id,
        ];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // TODO - pay attention to results
        return true;
    }

    public static function deleteById($id) : bool {
        $pdo = db::getPDO();

        $unmatched = ( (empty($id) || !static::checkForId($id)) );

        if ($unmatched) {
            throw new Exception("Could not delete: no matching record in database");
        }

        $sql = "DELETE FROM `".static::$tableName."` WHERE id=:id";
        $params = [
            "id" => $id,
        ];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // TODO - pay attention to results
        return true;
    }
}