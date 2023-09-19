<?php

class Letter extends Model {
    public int $playlist_id;
    public ?int $user_id;
    public string $letter;
    public ?string $spotify_track_id = null;
    public int $rank = 0;
    public string $cached_artist = '';
    public string $cached_title = '';

    public static $defaultOrderBy = ['rank','id'];

    static string $tableName = "letters";
    static $fields = ['id','playlist_id','user_id','letter','spotify_track_id','cached_artist','cached_title','rank','created','modified'];

    public function getPlaylist() : ?Playlist {
        return Playlist::getById($this->playlist_id);
    }

    public function getUser() : ?User {
        return User::getById($this->user_id);
    }
}