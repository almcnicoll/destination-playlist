<?php
if(!@include_once('inc/db.php')) { require_once('../inc/db.php'); }
if(!@include_once('class/model.php')) { require_once('../class/model.php'); }
if(!@include_once('class/user.php')) { require_once('../class/user.php'); }

class Playlist extends Model {
    public int $user_id;
    public string $destination;
    public ?string $spotify_playlist_id;
    public ?string $display_name;

    static string $tableName = "playlists";
    static $fields = ['id','user_id','destination','spotify_playlist_id','display_name','created','modified'];

    public function getUser() : ?User {
        return User::getById($this->user_id);
    }
}