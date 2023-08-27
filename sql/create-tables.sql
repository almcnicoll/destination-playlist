CREATE TABLE `authmethods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `methodName` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `handler` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `methodName` (`methodName`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `authmethod_id` int(10) unsigned NOT NULL,
  `identifier` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `LoginLookup` (`authmethod_id`,`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `playlists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `destination` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spotify_playlist_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flags` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ListIndex` (`user_id`,`destination`,`id`,`display_name`),
  CONSTRAINT `UserLookup` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `letters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `playlist_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `letter` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spotify_track_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cached_artist` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cached_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ListIndex` (`playlist_id`,`letter`,`id`,`cached_artist`,`cached_title`),
  CONSTRAINT `FK_playlist_lookup` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `participations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `playlist_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `IX_BY_USER` (`user_id`,`playlist_id`),
  UNIQUE KEY `IX_BY_PLAYLIST` (`playlist_id`,`user_id`),
  CONSTRAINT `FK_PLAYLIST` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_USER` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;