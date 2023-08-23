<?php
if(!@include_once('inc/db.php')) { require_once('../inc/db.php'); }
if(!@include_once('class/model.php')) { require_once('../class/model.php'); }
if(!@include_once('class/authmethod.php')) { require_once('../class/authmethod.php'); }

class User extends Model {
    private int $authmethod_id;
    public string $identifier;
    public ?string $email;

    static string $tableName = "users";
    static $fields = ['id','authmethod_id','identifier','email','created','modified'];

    public function set_authmethod_id($id) {
        $this->authmethod_id = $id;
    }
}