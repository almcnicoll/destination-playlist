/* UPDATE */
/* VERSION 1 */
ALTER TABLE letters ADD COLUMN rank INT NOT NULL DEFAULT 0 AFTER `spotify_track_id`;
/* UPDATE */
/* VERSION 2 */
UPDATE letters lOne
INNER JOIN
(
SELECT playlist_id, MIN(id) AS minid FROM letters
GROUP BY playlist_id
) lTwo ON lOne.playlist_id = lTwo.playlist_id
SET lOne.`rank` = lOne.id-lTwo.minid
;