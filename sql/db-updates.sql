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
/* UPDATE */
/* VERSION 3 */
ALTER TABLE users
ADD COLUMN image_url VARCHAR(500) DEFAULT NULL
AFTER market
;
/* UPDATE */
/* VERSION 4 */
DELETE FROM participations WHERE id IN
(SELECT id FROM
(
SELECT participations.id
FROM playlists
INNER JOIN participations ON participations.playlist_id = playlists.id AND participations.user_id = playlists.user_id
) t1
)
;
/* UPDATE */
/* VERSION 5 */
CREATE TABLE faqs (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(200)  COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer`   varchar(4000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rank` int(11) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ListIndex` (`rank`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;
/* UPDATE */
/* VERSION 6 */
INSERT INTO `faqs` (`rank`,`created`,`modified`,`question`,`answer`)
VALUES
(1,NOW(),NOW(),"What's the point of Destination Playlist? (for friends)","<p>On a long road trip, a good playlist can make the miles fly past. But do you listen to one person's choice of playlist, or mix &amp; match? And if you regularly spend time with the same people, how do you keep the choice fresh?</p><p>With Destination Playlist, you share the choices around, and having a starting letter forces you to think outside the box. Result: a unique playlist each time, and a chance to experience new music that you'd not otherwise have heard.</p>"),
(2,NOW(),NOW(),"What's the point of Destination Playlist? (for families)","<p>For families with younger kids, it's great to expose them to a range of music - plus there's only so many times you can listen to <em>Baby Shark</em> or the soundtrack to <em>Frozen</em>. Destination Playlist makes the process of introducing new music a fun one!</p><p>For families with teens, your taste in music probably has quite a small overlap. You might not want to listen to a whole playlist or album of a genre you don't like, but Destination Playlist makes a way to share some of your fave tracks without overload.</p>"),
(3,NOW(),NOW(),"Do I need a Spotify account?","Yes, at the moment you do need a Spotify&reg; account. Even if you aren't the playlist owner, you need to be able to search the Spotify catalogue, which requires you to be logged in. You can can make full use of Destination Playlist with a free Spotify account, which you can create <a href='https://www.spotify.com/signup' target='_blank'>here</a>."),
(4,NOW(),NOW(),"Does Destination Playlist cost anything?","<p>No, Destination Playlist is free to use. Enjoy it!</p><p>Having said that, it took quite a lot of work to make, and the server costs a bit to maintain too. If you'd like to make a small donation to support the app, you can do so with the 'Support me' link at the bottom of the page.</p>")
;
/* UPDATE */
/* VERSION 7 */
UPDATE `faqs` SET `answer`="<p>For families with younger kids, it's great to expose them to a range of music - plus there's only so many times you can listen to <em>Baby Shark</em> or the soundtrack to <em>Frozen</em> - although obviously you'll have to manage the playlist, as Spotify&reg; accounts are for 13s and over. Destination Playlist makes the process of introducing new music a fun one!</p><p>For families with teens, your taste in music probably has quite a small overlap. You might not want to listen to a whole playlist or album of a genre you don't like, but Destination Playlist makes a way to share some of your fave tracks without overload.</p>"
WHERE rank=2
;
UPDATE `faqs` SET `question` = REPLACE(`question`,'Spotify ','Spotify&reg; '),`answer` = REPLACE(`answer`,'Spotify ','Spotify&reg; ')
;
/* UPDATE */
/* VERSION 8 */
CREATE TABLE model (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;
CREATE TABLE errors (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `number` mediumint DEFAULT NULL,
  `message` VARCHAR(4000) DEFAULT NULL,
  `file` varchar(400) DEFAULT NULL,
  `line` smallint UNSIGNED DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;
/* UPDATE */
/* VERSION 9 */
INSERT INTO `faqs` (`rank`,`created`,`modified`,`question`,`answer`)
VALUES
(5,NOW(),NOW(),"How much of my data do you store, and what will you do with it?","<p>The short answer is that we store and use the minimum for the site to operate: your name, email and Spotify&reg; profile picture.</p><p>If there were to be a site security breach which required us to notify users, we would use your email address to do so - the same would be true in the event of a major change to the service which might require your attention. Beyond that, you will not receive emails from us unless you explicitly 'opt in' to do so.</p><p>The full website privacy policy is available <a href='../privacy/policy'>here</a>.</p>")
;
/* UPDATE */
/* VERSION 10 */
CREATE TABLE `integers` (
  `number` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`number`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;
insert  into `integers`(`number`) values (1),(2),(3),(4),(5),(6),(7),(8),(9),(10),(11),(12),(13),(14),(15),(16),(17),(18),(19),(20),(21),(22),(23),(24),(25)
,(26),(27),(28),(29),(30),(31),(32),(33),(34),(35),(36),(37),(38),(39),(40),(41),(42),(43),(44),(45),(46),(47),(48),(49),(50),
(51),(52),(53),(54),(55),(56),(57),(58),(59),(60),(61),(62),(63),(64),(65),(66),(67),(68),(69),(70),(71),(72),(73),(74),(75),
(76),(77),(78),(79),(80),(81),(82),(83),(84),(85),(86),(87),(88),(89),(90),(91),(92),(93),(94),(95),(96),(97),(98),(99),(100)
;