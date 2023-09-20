<?php

class Participation extends Model {
    public int $user_id;
    public string $playlist_id;
    public int $removed = 0;

    static string $tableName = "participations";
    static $fields = ['id','user_id','playlist_id','removed','created','modified'];

    public function getUser() : ?User {
        return User::getById($this->user_id);
    }
    
    public function getPlaylist() : ?Playlist {
        return Playlist::getById($this->playlist_id);
    }
}