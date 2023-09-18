/* UPDATE */
/* VERSION 1 */
ALTER TABLE letters ADD COLUMN rank INT NOT NULL DEFAULT 0 AFTER `spotify_track_id`;
