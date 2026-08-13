if (typeof tourGuide === 'undefined') { var tourGuide = {}; }

// Canonical step registry, keyed by concept rather than by page - playlist_join.php and
// playlist_manage.php share several element IDs (track-search-box, toggle-my-letters, play-button),
// so a step marked seen on one page is never shown again on the other. Keep this list's keys in sync
// with TourStep::$knownKeys in class/TourStep.php.
tourGuide.registry = {
    'play-devices': {
        element: '#play-button',
        popover: {
            title: 'Play the playlist',
            description: "Click here any time to play the destination playlist on one of your Spotify devices."
        }
    },
    'my-letters-toggle': {
        element: '#toggle-my-letters',
        popover: {
            title: 'Show my letters only',
            description: "Flip this switch to filter the table down to just the letters you're responsible for."
        }
    },
    'track-search': {
        element: '#track-search-box',
        popover: {
            title: 'Search for a track',
            description: "Click the pencil icon next to any of your letters to search Spotify and pick a track that starts with it."
        },
        // The search box only exists inside a modal, so we open it just for this step and close
        // it again afterwards - see js/search_mgmt.js, which has no side effects on modal close.
        onHighlightStarted: function() {
            var modalEl = document.getElementById('trackSearchModal');
            if (modalEl) { bootstrap.Modal.getOrCreateInstance(modalEl).show(); }
        },
        onDeselected: function() {
            var modalEl = document.getElementById('trackSearchModal');
            if (modalEl) { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); }
        }
    },
    'swap-letter': {
        // Deliberately no element/modal for this one: #swapMarketModal's own 'hidden.bs.modal'
        // handler (js/swap_market.js) calls swapMarket.withdraw() on close, which would fire a
        // pointless (if harmless) AJAX call every time this step ended. A centered popover with
        // no target avoids needing to open that modal at all.
        element: null,
        popover: {
            title: 'Stuck with a tricky letter?',
            description: "Once you've got a letter, look for the swap icon next to it — it puts your letter up for swap with someone else's."
        }
    },
    'assign-letters': {
        element: '#btn-assign-letters',
        popover: {
            title: 'Assign letters',
            description: "Once everyone's joined, click here to hand out a letter to each participant. \"Reassign\" clears and redoes it if the group changes."
        }
    },
    'lock-list': {
        element: '#btn-lock-list',
        popover: {
            title: 'Lock the playlist',
            description: "Lock it once your group is settled, so no one new can join using the share link."
        }
    },
    'people-tab': {
        element: '#nav1-tab-1',
        popover: {
            title: "See who's joined",
            description: "The People tab lists everyone in the playlist. You can remove someone from here too, which frees up their letter."
        }
    },

    // playlist listing page (pages/index.php)
    'my-playlists-section': {
        element: '#my-playlists-heading',
        popover: {
            title: 'Your playlists',
            description: "Playlists you've created live here."
        }
    },
    'pick-tracks': {
        element: '#my-playlists-table tbody tr:first-child .pick-tracks-btn',
        popover: {
            title: 'Pick tracks',
            description: "Opens the playlist so you (and your guests) can pick a track for each letter."
        }
    },
    'edit-playlist': {
        element: '#my-playlists-table tbody tr:first-child .edit-playlist-btn',
        popover: {
            title: 'Edit playlist',
            description: "Changes the playlist's setup — its destination word, matching rules, and so on — rather than the tracks themselves."
        }
    },
    'share-playlist': {
        element: '#my-playlists-table tbody tr:first-child .share-playlist-btn',
        popover: {
            title: 'Share playlist',
            description: "Get an invite link to send to friends so they can join in."
        }
    },
    'delete-playlist': {
        element: '#my-playlists-table tbody tr:first-child .delete-playlist-btn',
        popover: {
            title: 'Delete playlist',
            description: "Deletes the playlist — you can choose to remove it from Spotify too, or just from here."
        }
    },
    'joined-playlists-section': {
        element: '#joined-playlists-heading',
        popover: {
            title: "Playlists you've joined",
            description: "Playlists other people have invited you to — you can pick tracks here too, just like your own."
        }
    }
};

tourGuide.markSeenUrl = root_path + "/ajax/mark_tour_step.php";

// Tracks every step this user has seen, cumulatively across pages/tours in this session -
// seeded from the server in init(), and topped up client-side as steps are highlighted.
tourGuide.seenSet = tourGuide.seenSet || new Set();

tourGuide.markSeen = function(stepKey) {
    tourGuide.seenSet.add(stepKey);
    $.ajax(tourGuide.markSeenUrl, {
        async: true, cache: false, dataType: 'json', method: 'POST', timeout: 8000,
        data: { step_key: stepKey }
        // Deliberately no error handling - if this fails the step just gets explained again next
        // time, which is a harmless annoyance rather than something worth retrying for.
    });
};

// Closing a tour via its X means "stop showing me these automatically", not "I've seen this
// one step" - so every step, app-wide, gets marked seen in one request (see
// TourStep::markAllSeen), not just the ones this particular tour happened to include. One
// request rather than one per step also means a page-reload right after closing can't race and
// abort only some of them, which is what caused steps to keep reappearing after a refresh.
tourGuide.markAllSeen = function() {
    Object.keys(tourGuide.registry).forEach(function(key) { tourGuide.seenSet.add(key); });
    $.ajax(tourGuide.markSeenUrl, {
        async: true, cache: false, dataType: 'json', method: 'POST', timeout: 8000,
        data: { all: 1 }
    });
};

tourGuide.replayModalId = 'tourReplayModal';

tourGuide.ensureReplayModal = function() {
    if (document.getElementById(tourGuide.replayModalId)) { return; }
    // Built at runtime rather than duplicated as static markup in every page that includes this
    // script (index.php, playlist_join.php, playlist_manage.php) - shape matches the app's other
    // modals (e.g. #playlistDeleteModal in pages/index.php).
    var html = ""
        + "<div class='modal fade' id='" + tourGuide.replayModalId + "' tabindex='-1'>"
        +   "<div class='modal-dialog'>"
        +     "<div class='modal-content'>"
        +       "<div class='modal-header'>"
        +         "<h5 class='modal-title'>Guided tour turned off</h5>"
        +         "<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>"
        +       "</div>"
        +       "<div class='modal-body'>"
        +         "<p>You won't see these tour tips pop up automatically any more.</p>"
        +         "<p class='mb-0'>You can bring them back any time from your account menu &rarr; <strong>Replay tour</strong>.</p>"
        +       "</div>"
        +       "<div class='modal-footer'>"
        +         "<button type='button' class='btn btn-md btn-success' data-bs-dismiss='modal'>Got it</button>"
        +       "</div>"
        +     "</div>"
        +   "</div>"
        + "</div>";
    $('body').append(html);
};

tourGuide.showReplayModal = function() {
    tourGuide.ensureReplayModal();
    bootstrap.Modal.getOrCreateInstance(document.getElementById(tourGuide.replayModalId)).show();
};

tourGuide.buildSteps = function(keys) {
    var steps = [];
    keys.forEach(function(key) {
        var def = tourGuide.registry[key];
        if (!def) { return; }
        var step = {
            popover: { title: def.popover.title, description: def.popover.description },
            onHighlightStarted: function() {
                if (def.onHighlightStarted) { def.onHighlightStarted(); }
            },
            onHighlighted: function() {
                tourGuide.markSeen(key);
            },
            onDeselected: function() {
                if (def.onDeselected) { def.onDeselected(); }
            }
        };
        if (def.element) { step.element = def.element; }
        steps.push(step);
    });
    return steps;
};

tourGuide.run = function(keys) {
    if (typeof window.driver === 'undefined' || !keys || keys.length === 0) { return; }
    var closedViaCross = false;
    var driverObj = window.driver.js.driver({
        showProgress: true,
        allowClose: true,
        steps: tourGuide.buildSteps(keys),
        // Providing our own onCloseClick replaces driver.js's default entirely (which is just
        // "destroy") - Escape and backdrop-click both call destroy directly without going through
        // this hook, so this only fires for an actual click on the popover's X. That click means
        // "turn the tour off", so it marks everything seen (not just this tour's steps) before
        // destroying, rather than leaving the rest to reappear on the next page/reload.
        onCloseClick: function() {
            closedViaCross = true;
            tourGuide.markAllSeen();
            driverObj.destroy();
        },
        // Fires however the tour ends (X, Escape, backdrop click, or finishing normally via
        // "Done") - only show the "how to turn it back on" hint for the X-close case.
        onDestroyed: function() {
            if (closedViaCross) {
                tourGuide.showReplayModal();
            }
        }
    });
    driverObj.drive();
};

// applicableKeys: which concepts exist on the current page. seenKeys: which ones this user has
// already had explained (on this page or any other) - see ajax/mark_tour_step.php.
tourGuide.init = function(applicableKeys, seenKeys) {
    tourGuide.applicableKeys = applicableKeys || [];
    tourGuide.seenSet = new Set(seenKeys || []);
    var unseen = tourGuide.applicableKeys.filter(function(key) {
        return !tourGuide.seenSet.has(key);
    });
    tourGuide.run(unseen);
};

// Wired to the "Replay tour" link in inc/header.php. This is a genuine global reset, not just
// "show this page's steps regardless of seen-state" - otherwise every *other* page would still
// think everything had already been seen (from the last X-dismissal) and show nothing on its
// own next visit, which defeats the point of restarting the tour.
tourGuide.replay = function(applicableKeys) {
    tourGuide.seenSet = new Set();
    $.ajax(tourGuide.markSeenUrl, {
        async: true, cache: false, dataType: 'json', method: 'POST', timeout: 8000,
        data: { reset: 1 }
    });
    tourGuide.run(applicableKeys || tourGuide.applicableKeys || []);
};
