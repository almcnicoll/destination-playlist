<?php
if(!@include_once('inc/db.php')) { require_once('../inc/db.php'); }
if(!@include_once('class/model.php')) { require_once('../class/model.php'); }
if(!@include_once('class/user.php')) { require_once('../class/user.php'); }

class Letter extends Model {
    public int $playlist_id;
    public ?int $user_id;
    public string $letter;
    public ?string $spotify_track_id = null;
    public string $cached_artist = '';
    public string $cached_title = '';

    static string $tableName = "letters";
    static $fields = ['id','playlist_id','user_id','letter','spotify_track_id','cached_artist','cached_title','created','modified'];

    public function getPlaylist() : ?Playlist {
        return Playlist::getById($this->playlist_id);
    }

    public function getUser() : ?User {
        return User::getById($this->user_id);
    }
}