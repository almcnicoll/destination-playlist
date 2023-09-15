<?php
if(!@include_once('inc/db.php')) { require_once('../inc/db.php'); }
if(!@include_once('class/model.php')) { require_once('../class/model.php'); }
if(!@include_once('class/user.php')) { require_once('../class/user.php'); }
if(!@include_once('class/spotifyrequest.php')) { require_once('../class/spotifyrequest.php'); }

class Playlist extends Model {

    const FLAGS_STRICT          =   1;  // Should DP prevent non-matching tracks from being added?
    const FLAGS_ALLOWTITLE      =   2;  // Can track title be used for the relevant letter?
    const FLAGS_ALLOWARTIST     =   4;  // Can artist be used for the relevant letter?
    const FLAGS_THEAGNOSTIC     =   8;  // If yes, 'The' can be ignored or used
    const FLAGS_INCLUDEDIGITS   =  16;  // If yes, digits as well as letters are used as match targets
    const FLAGS_PEOPLELOCKED    =  32;  // If yes, nobody new can join the playlist

    public int $user_id;
    public string $destination;
    public ?string $spotify_playlist_id;
    public ?string $display_name;
    public int $flags;

    static string $tableName = "playlists";
    static $fields = ['id','user_id','destination','spotify_playlist_id','display_name','flags','created','modified'];

    public function getOwner() : ?User {
        return User::getById($this->user_id);
    }
    public function getParticipants() {
        return Participation::find(['playlist_id','=',$this->id]);
    }
    
    public function getLetters() {
        return Letter::find([['playlist_id','=',$this->id],]);
    }
    public function getUnassignedLetters() {
        return Letter::find([['playlist_id','=',$this->id],['user_id','IS',null]]);
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

    public function clearLetterOwners() : void {
        $pdo = db::getPDO();
        $sql = "UPDATE `".Letter::$tableName."` SET user_id = NULL WHERE playlist_id = :playlist_id";
        $criteria_values = [
            'playlist_id' => $this->id,
        ];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($criteria_values);
    }

    public function setImage($image = null) {
        global $config;
        if ($image == null) { $image = $config['local_root'].'/img/dp-logo.jpg'; }
        if (!file_exists($image)) { return "File \"{$image}\" does not exist"; }
        $imagedata = file_get_contents($image);
        $base64 = base64_encode($imagedata);
        $size = strlen($base64);
        if ($size > (256*1024)) { return "File too large"; } // Size limit imposed by Spotify
        $endpoint = "https://api.spotify.com/v1/playlists/{$this->spotify_playlist_id}/images";
        $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_PUT, $endpoint);
        $sr->send($base64);
        if (($sr->result!==false) && ($sr->error_number==0) && ($sr->http_code < 400)) {
            return true;
        } else {
            if ($sr->http_code >= 400) {
                return "Request URL: {$endpoint}\n"
                        ."Request returned ".$sr->http_code.': '.$sr->result;
            } else {
                return "Error #".$sr->error_number.": ".$sr->error_message;
            }
        }
    }

    /*
    // At some point, implement this to have limited-duration easy-to-share codes
    public function setShareCode() {
        // Auto-sets the share-code for the playlist
    }
    */
}