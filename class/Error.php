<?php

class Error extends Model {

    const TYPE_PHP          =   'php';
    const TYPE_CURL         =   'curl';
    const TYPE_OTHER        =   'other';

    public int $type;
    public ?int $number;
    public ?string $file;
    public ?int $line;
    public ?string $message;

    static string $tableName = "errors";
    static $fields = ['id','type','file','line','number','message','created','modified'];

    public static $defaultOrderBy = [
        ['created','DESC'],
        ['id','ASC'],
    ];

}