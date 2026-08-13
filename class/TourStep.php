<?php

class TourStep extends Model {
    public int $user_id;
    public string $step_key;

    static string $tableName = "tour_seen_steps";
    static $fields = ['id','user_id','step_key','created','modified'];

    // Mirrors the step registry in js/tour_guide.js - kept in sync manually, since the two
    // sides need to agree on what a "step" is without sharing a build step.
    static $knownKeys = [
        'play-devices',
        'my-letters-toggle',
        'track-search',
        'swap-letter',
        'assign-letters',
        'lock-list',
        'people-tab',
        'my-playlists-section',
        'pick-tracks',
        'edit-playlist',
        'share-playlist',
        'delete-playlist',
        'joined-playlists-section',
    ];

    public static function markSeen(int $userId, string $stepKey) : void {
        $existing = self::findFirst([['user_id','=',$userId],['step_key','=',$stepKey]]);
        if ($existing !== null) { return; }
        $step = new TourStep();
        $step->user_id = $userId;
        $step->step_key = $stepKey;
        $step->save();
    }

    public static function getSeenKeys(int $userId) : array {
        $seen = self::find([['user_id','=',$userId]]);
        return array_map(function($s) { return $s->step_key; }, $seen);
    }
}
