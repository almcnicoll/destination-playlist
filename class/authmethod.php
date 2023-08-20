<?php
if(!@include_once('inc/db.php')) { require_once('../inc/db.php'); }

class AuthMethod {
    public int $id;
    public string $methodName;
    public string $handler;
    public ?string $image;
    private ?string $created;
    private ?string $modified;
    public ?DateTime $dateCreated; // TODO - transform this to getter reading private variable
    public ?DateTime $dateModified; // TODO - transform this to getter reading private variable

    static string $tableName = "authmethods";

    public static function getAll() : array {
        $pdo = db::getPDO();
        
        $sql = "SELECT * FROM `".AuthMethod::$tableName."`";
        $query = $pdo->query($sql);

        $results = $query->fetchAll(PDO::FETCH_CLASS, "AuthMethod");
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

        $result = $stmt->fetch(PDO::FETCH_CLASS,"AuthMethod");
        if ($result === false) { return null; }
        return $result;
    }
}