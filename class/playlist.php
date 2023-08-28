<?php
if(!@include_once('inc/db.php')) { require_once('../inc/db.php'); }
if(!@include_once('class/model.php')) { require_once('../class/model.php'); }
if(!@include_once('class/user.php')) { require_once('../class/user.php'); }

class Playlist extends Model {

    const FLAGS_STRICT          =   1;  // Should DP prevent non-matching tracks from being added?
    const FLAGS_ALLOWTITLE      =   2;  // Can track title be used for the relevant letter?
    const FLAGS_ALLOWARTIST     =   4;  // Can artist be used for the relevant letter?
    const FLAGS_THEAGNOSTIC     =   8;  // If yes, 'The' can be ignored or used
    const FLAGS_INCLUDEDIGITS   =  16;  // If yes, digits as well as letters are used as match targets

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
    
    public function getLetters() {
        return Letter::find([['playlist_id','=',$this->id],]);
    }

    public function hasFlags(...$testFlags) : bool {
        $flagSum = 0;
        foreach ($testFlags as $f) {
            $flagSum = $flagSum | $f;
        }
        return ($this->flags & $flagSum == $flagSum);
    }

    public function getShareCode() : string {
        $hash = hash('sha256', (string)$this->id);
        $second_pos = $this->id % 64;
        return $this->id . '-' . substr($hash, 0, 1) . substr($hash, $second_pos, 1);
    }

    /*
    // At some point, implement this to have limited-duration easy-to-share codes
    public function setShareCode() {
        // Auto-sets the share-code for the playlist
    }
    */
}