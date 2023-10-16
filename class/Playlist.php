<?php

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

    public static $defaultOrderBy = [
        ['created','DESC'],
        ['display_name','ASC'],
    ];

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
        $compResult = $this->flags & $flagSum;
        return ($compResult == $flagSum);
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

    public function existsOnSpotify() : bool {
        global $config;
        if (empty($this->spotify_playlist_id)) { return false; } // No id therefore can't exist
        $endpoint = "https://api.spotify.com/v1/playlists/{$this->spotify_playlist_id}";
        $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_GET, $endpoint);
        $sr->send();
        return !$sr->hasErrors(); // If there's no errors, it exists - a bit crude because we might have a quota error or something
    }

    // Checks if the playlist exists on Spotify - if not, creates it
    public function pushToSpotify() {
        global $config;
        $user = $_SESSION['USER'];
        // We need to re-create if (a) there is no spotify playlist ID saved or (b) Spotify doesn't recognise the id
        if ((!empty($this->spotify_playlist_id)) && ($this->existsOnSpotify())) {
            $endpoint = "https://api.spotify.com/v1/playlists/{$this->spotify_playlist_id}/";
            $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_PUT, $endpoint);
            $sr->contentType = SpotifyRequest::CONTENT_TYPE_JSON;
            $editData = [
                'name'              => $this->display_name,
                'public'            => true,
                'collaborative'     => false,
                /*'description'       => "Created by Destination Playlist: ".date('jS M Y, H:i'),*/ // Don't overwrite
            ];
            
            return $sr->send($editData);
        } else {
            $endpoint = "https://api.spotify.com/v1/users/{$user->identifier}/playlists";
            $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_PUT, $endpoint);
            $createdData = [
                'name'              => $this->display_name,
                'public'            => true,
                'collaborative'     => false,
                'description'       => "Created by Destination Playlist: ".date('jS M Y, H:i'),
            ];
            $sr->send($createdData);
            if ($sr->hasErrors()) {
                return $sr;
            } else {
                $result = json_decode($sr->result);
                $this->spotify_playlist_id = $result->id;
                return $sr;
            }
        }

        
    }

    // This function resets the ranks of the letters to start at 1 and increase from there
    public function tidyLetterRanks() : void {
        $dbo = db::getPDO();
        $sql = <<<END_SQL
UPDATE letters lOne
INNER JOIN
(
SELECT playlist_id, MIN(id) AS minid FROM letters
GROUP BY playlist_id
) lTwo ON lOne.playlist_id = lTwo.playlist_id
SET lOne.`rank` = lOne.id-lTwo.minid
WHERE lOne.playlist_id = :playlist_id
;
END_SQL;
        $stmt = $dbo->prepare($sql);
        $stmt->execute(['playlist_id' => $this->id]);
        $stmt->closeCursor();
    }

    // This function increases the rank of letters at or after a certain index by an offset, so more letters can be inserted before them
    public function makeLetterSpaceAt($index, $offset) : int {
        if (!is_numeric($index)) { throw new Exception("Number expected for argument \$index."); }
        if (!is_numeric($offset)) { throw new Exception("Number expected for argument \$offset."); }
        $letters = $this->getLetters();
        $changes = 0;
        foreach ($letters as $letter) {
            if ($letter->rank >= $index) {
                $letter->rank += $offset;
                $letter->save();
                $changes++;
            }
        }
        return $changes;
    }

    /*
    // At some point, implement this to have limited-duration easy-to-share codes
    public function setShareCode() {
        // Auto-sets the share-code for the playlist
    }
    */
}