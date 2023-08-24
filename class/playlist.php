<?php
if(!@include_once('inc/db.php')) { require_once('../inc/db.php'); }
if(!@include_once('class/model.php')) { require_once('../class/model.php'); }
if(!@include_once('class/user.php')) { require_once('../class/user.php'); }

/*enum PlaylistFlags : int {
    case Strict = 1; // Should DP prevent non-matching tracks from being added?
    case AllowTitle = 2; // Can track title be used for the relevant letter?
    case AllowArtist = 4; // Can artist be used for the relevant letter?
    case TheAgnostic = 8; // If yes, 'The' can be ignored or used
}*/

class Playlist extends Model {
    public int $user_id;
    public string $destination;
    public ?string $spotify_playlist_id;
    public ?string $display_name;
    public int $flags;

    static string $tableName = "playlists";
    static $fields = ['id','user_id','destination','spotify_playlist_id','display_name','flags','created','modified'];

    public function getUser() : ?User {
        return User::getById($this->user_id);
    }
}