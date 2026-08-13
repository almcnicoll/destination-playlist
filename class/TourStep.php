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

    // Marks every known step seen in one request - used when the user closes a tour via its X
    // (see js/tour_guide.js), which means "stop showing me these automatically", not "I've
    // literally seen every popup". Doing this server-side in one round trip, rather than the
    // client firing one request per step, avoids a page-reload racing and only aborting some of
    // them mid-flight.
    public static function markAllSeen(int $userId) : void {
        foreach (self::$knownKeys as $stepKey) {
            self::markSeen($userId, $stepKey);
        }
    }

    // Clears every step for this user - "Replay tour" (js/tour_guide.js) uses this so the reset
    // is global, not just for the page it was clicked from. Without this, replaying only ever
    // showed the current page's steps (which it shows regardless of seen-state anyway); every
    // *other* page would still see the user as having seen everything, from the last time the
    // tour was dismissed via its X.
    public static function resetAll(int $userId) : void {
        foreach (self::find([['user_id','=',$userId]]) as $row) {
            $row->delete();
        }
    }
}
