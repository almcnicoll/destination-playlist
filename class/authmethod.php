<?php
if(!@include_once('inc/db.php')) { require_once('../inc/db.php'); }
if(!@include_once('class/model.php')) { require_once('../class/model.php'); }

class AuthMethod extends Model {
    public int $id;
    public string $methodName;
    public string $handler;
    public ?string $image;
    
    static string $tableName = "authmethods";
}