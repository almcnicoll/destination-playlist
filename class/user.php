<?php
// TODO - add market field

if(!@include_once('inc/db.php')) { require_once('../inc/db.php'); }
if(!@include_once('class/model.php')) { require_once('../class/model.php'); }
if(!@include_once('class/authmethod.php')) { require_once('../class/authmethod.php'); }

class User extends Model {
    public int $authmethod_id;
    public string $identifier;
    public ?string $email;
    public ?string $display_name;
    public ?string $market;

    static string $tableName = "users";
    static $fields = ['id','authmethod_id','identifier','email','display_name','market','created','modified'];

    public function setAuthmethod_id($id) {
        $this->authmethod_id = $id;
    }

    public function getAuthmethod() : ?AuthMethod {
        return AuthMethod::getById($this->authmethod_id);
    }
}