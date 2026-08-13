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

    public function setFlag($whichFlag, $flagValue) : void {
        // Set the specified flag to the specified value
        if ($flagValue) {
            // Set the flag to true
            if ($this->hasFlags($whichFlag)) { return; } // Return if already true
            $this->flags = (int)$this->flags + (int)$whichFlag;
        } else {
            // Set the flag to false
            if (!$this->hasFlags($whichFlag)) { return; } // Return if already false
            $this->flags = (int)$this->flags - (int)$whichFlag;
        }
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

    // Explicitly follows the playlist as the current session's user. Spotify normally auto-follows a
    // playlist for whoever creates it via the API, but some account types (e.g. Family-plan child
    // accounts) don't get that automatic library add, so the owner-creation paths call this explicitly
    // too - mirroring what playlist_join.php already does for participants.
    public function followOnSpotify() : SpotifyRequest {
        $endpoint = "https://api.spotify.com/v1/playlists/{$this->spotify_playlist_id}/followers";
        $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_PUT, $endpoint);
        return $sr->send();
    }

    public function existsOnSpotify() : bool {
        global $config;
        if (empty($this->spotify_playlist_id)) { return false; } // No id therefore can't exist
        $endpoint = "https://api.spotify.com/v1/playlists/{$this->spotify_playlist_id}";
        $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_GET, $endpoint);
        $sr->send();
        return !$sr->hasErrors(); // If there's no errors, it exists - a bit crude because we might have a quota error or something
    }

    // Pushes name/public/collaborative settings to an already-existing Spotify playlist
    public function updateDetailsOnSpotify() : SpotifyRequest {
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
    }

    // Checks if the playlist exists on Spotify - if not, creates it
    public function pushToSpotify() {
        global $config;
        // We need to re-create if (a) there is no spotify playlist ID saved or (b) Spotify doesn't recognise the id
        if ((!empty($this->spotify_playlist_id)) && ($this->existsOnSpotify())) {
            return $this->updateDetailsOnSpotify();
        } else {
            // Playlist creation is POST /me/playlists (POST /users/{id}/playlists was removed by Spotify)
            $endpoint = "https://api.spotify.com/v1/me/playlists";
            $sr = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_POST, $endpoint);
            $sr->contentType = SpotifyRequest::CONTENT_TYPE_JSON;
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
                $this->save(); // Persist the new id, or every future request will try to re-create it again
                $this->followOnSpotify();
                // Spotify's create endpoint doesn't reliably honour `public` in the request body -
                // set it explicitly with a follow-up call, same as the edit path above
                $this->updateDetailsOnSpotify();
                return $sr;
            }
        }


    }

    // Rebuilds the full Spotify track list for this playlist from the DB (in letter rank order) and pushes it.
    // Returns ['success' => bool, 'errors' => string[], 'http_code' => ?int]. If another request is already
    // pushing this playlist, this call is a no-op success - that other push will leave Spotify in the right state.
    public function pushTracksToSpotify() : array {
        $pdo = db::getPDO();

        $lockName = 'playlist_push_'.(int)$this->id;
        $stmtGetLock = $pdo->prepare('SELECT GET_LOCK(:lockname, 0) AS locked');
        $stmtGetLock->execute(['lockname' => $lockName]);
        $lockResult = $stmtGetLock->fetch(PDO::FETCH_ASSOC);
        $lockAcquired = ($lockResult !== false) && ((int)$lockResult['locked'] === 1);
        if (!$lockAcquired) {
            return ['success' => true, 'errors' => [], 'http_code' => null];
        }

        $errors = [];
        $success = true;

        // Order by rank (the letters' actual display/spelling order), falling back to id for ties -
        // ordering by id alone put newly-inserted letters at the end instead of their correct position
        $sqlGetTracks = <<<END_SQL
        SELECT spotify_track_id FROM letters
        WHERE spotify_track_id IS NOT NULL
        AND playlist_id = :playlist_id
        ORDER BY `rank`,id
        ;
END_SQL;
        $stmtGetTracks = $pdo->prepare($sqlGetTracks);
        $stmtGetTracks->execute(['playlist_id' => $this->id]);
        $trackIds = $stmtGetTracks->fetchAll(PDO::FETCH_COLUMN);
        // An empty array here is a valid "empty playlist" state, not "nothing to do" - push it
        // through so a fully-cleared playlist actually gets cleared on Spotify too
        $trackUris = array_map(fn($id) => 'spotify:track:'.$id, $trackIds);

        // Sent as a JSON body rather than a query string - Spotify's own docs warn that large uri
        // lists in the query string can silently exceed URL length limits and get truncated
        $endpoint = "https://api.spotify.com/v1/playlists/".$this->spotify_playlist_id."/items";
        $srUpdatePlaylist = new SpotifyRequest(SpotifyRequest::TYPE_API_CALL, SpotifyRequest::ACTION_PUT, $endpoint);
        $srUpdatePlaylist->contentType = SpotifyRequest::CONTENT_TYPE_JSON;
        $srUpdatePlaylist->send(['uris' => $trackUris]);
        if (($srUpdatePlaylist->result !== false) && ($srUpdatePlaylist->error_number==0) && ($srUpdatePlaylist->http_code < 400)) {
            // All good
        } else {
            $success = false;
            if ($srUpdatePlaylist->http_code >= 400) {
                $errors[] = "Request URL: {$endpoint}";
                $errors[] = "Request returned ".$srUpdatePlaylist->http_code.': '.$srUpdatePlaylist->result;
            } else {
                $errors[] = $srUpdatePlaylist->error_message;
            }
        }

        $stmtReleaseLock = $pdo->prepare('SELECT RELEASE_LOCK(:lockname)');
        $stmtReleaseLock->execute(['lockname' => $lockName]);

        return ['success' => $success, 'errors' => $errors, 'http_code' => $srUpdatePlaylist->http_code];
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