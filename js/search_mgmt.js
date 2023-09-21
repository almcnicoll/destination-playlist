if (typeof trackSearch === 'undefined') { trackSearch = {}; }

trackSearch.search_request_queue = null;
trackSearch.search_request_running = false;

trackSearch.reThe = /^the\s+/i; // Matches "the" at the start of a string, case-insensitive
trackSearch.reArtistSplit = /\s*\/\/\s*/; // Matches two forward-slashes with any leading/trailing whitespace

trackSearch.build_search_request = function(txt) {
    var querystring = '';
    if (trackSearch.allow_title && trackSearch.allow_artist) {
        querystring = encodeURIComponent(txt);
    } else if (trackSearch.allow_title) {
        querystring = encodeURIComponent("track:"+txt);
    } else if (trackSearch.allow_artist) {
        querystring = encodeURIComponent("artist:"+txt);
    } else {
        // Not sure what to do here!
        querystring = encodeURIComponent(txt);
    }
    if (!'limit' in trackSearch) { trackSearch.limit = 40; }
    return {
        query: querystring,
        resultType: 'track',
        market: trackSearch.market,
        limit: trackSearch.limit
    };
}

trackSearch.search_request = function(query,resultType,userMarket,resultLimit) {
    trackSearch.ajaxOptions.data = {
        q: query,
        type: resultType,
        market: userMarket,
        limit: resultLimit,
        playlist_id: trackSearch.playlist_id
    };
    $('#search_spinner').removeClass('hidden');
    $.ajax(root_path + '/ajax/proxy_search.php', trackSearch.ajaxOptions);
}

trackSearch.updateSearchBox = function(data, textStatus, jqXHR) {
    if ('updateSearchBoxCustom' in trackSearch) {
        trackSearch.updateSearchBoxCustom(data, textStatus, jqXHR); // Runs any custom actions for the page on which we're embedding
    }
}

trackSearch.processQueue = function() {
    trackSearch.search_request_running = false;
    $('#search_spinner').addClass('hidden');
    if (trackSearch.search_request_queue !== null) {
        // There was another request waiting in the wings
        // Stash variables
        var _q  = trackSearch.search_request_queue.query;
        var _rT = trackSearch.search_request_queue.resultType;
        var _m  = trackSearch.search_request_queue.market;
        var _l  = trackSearch.search_request_queue.limit;
        // Clear queue
        trackSearch.search_request_queue = null;
        // Send queued request
        trackSearch.search_request(_q,_rT,_m,_l);
    }
}

trackSearch.handleTrackUpdate = function(jqXHR, textStatus) {
    // "success", "notmodified", "nocontent", "error", "timeout", "abort", or "parsererror"
    $("html,html *").css("cursor","auto"); // Reset cursor

    switch (textStatus) {
        case 'success':
            if ('handleTrackUpdateSuccessCustom' in trackSearch) {
                trackSearch.handleTrackUpdateSuccessCustom(); // Runs any custom actions for the page on which we're embedding
            }
            break;
        case 'error':
            if ('handleTrackUpdateErrorCustom' in trackSearch) {
                trackSearch.handleTrackUpdateErrorCustom(); // Runs any custom actions for the page on which we're embedding
            } else {
                alert("Error saving selection. Please try again.");
            }
            break;
        case 'timeout':
            if ('handleTrackUpdateTimeoutCustom' in trackSearch) {
                trackSearch.handleTrackUpdateTimeoutCustom(); // Runs any custom actions for the page on which we're embedding
            } else {
                alert("The server did not respond in time. Please try again.");
            }
            break;
        default:
            break;
    }
}

trackSearch.ajaxOptions = {
    async: true,
    cache: false,
    success: trackSearch.updateSearchBox,
    complete: trackSearch.processQueue,
    dataType: 'json',
    method: 'GET',
    timeout: 10000,
    headers: {
        Authorization: 'Bearer '+trackSearch.token,
        'Content-type': 'application/json'
    }
};

trackSearch.checkTrack = function(trackName, artistName) {
    // Checks if this track is valid, using the relevant options
    // Return true at the first sign of success

    if (!trackSearch.strict_mode) { return true; } // Unless we're in strict mode, allow all tracks

    // Create arrays of the variants to check - empty if that field isn't valid for search
    var tracks = new Array();
    if (trackSearch.allow_title) {
        // We can validate on title
        trackName = trackName.toUpperCase(); // Match in uppercase, matching trackSearch.search_letter
        tracks.push(trackName.replace(trackSearch.reThe,'')); // Always include a "no the" variant
        if (trackSearch.the_agnostic) {
            tracks.push(trackName);
        }
    }
    // Now check them
    for (var i in tracks) {
        if (tracks[i].substr(0,1) == trackSearch.search_letter) {return true;}
    }

    // Repeat process for artists - but they can be comma-separated
    var artists = new Array();
    if (trackSearch.allow_artist) {
        // Split artist names by separator (//)
        var splitArtists = artistName.toUpperCase().split(this.reArtistSplit); // Match in uppercase, matching trackSearch.search_letter
        for (var i in splitArtists) {
            artists.push(splitArtists[i].replace(trackSearch.reThe,'')); // Always include a "no the" variant
            if (trackSearch.the_agnostic) {
                artists.push(splitArtists[i]);
            }
        }
    }
    // Now check them
    for (var i in artists) {
        if (artists[i].substr(0,1) == trackSearch.search_letter) {return true;}
    }

    // If we got here, there's no valid matches
    return false;
}

trackSearch.init = function(inputBox, outputBox, limit=20) {
    trackSearch.limit = limit;
    $(document).ready(function() {
        // Handle typing in search box
        $(inputBox).on('keyup',function(event) {
            // Only deal with actual characters
            if (((event.which < 40)||(event.which > 90)) && (event.which != 8)) {
                return;
            }
            // Don't run loads of simultaneous queries
            var txt=$(this).val();
            if (trackSearch.search_request_running) {
                // Set or overwrite queued request, ready for completion of current one
                trackSearch.search_request_queue = trackSearch.build_search_request(txt);
            } else {
                if (txt.length > 3) {
                    trackSearch.search_request_running = true;
                    var req = trackSearch.build_search_request(txt);
                    trackSearch.search_request(req.query,req.resultType,req.market,req.limit);
                }
            }
        });

        // Handle clicking on a search result
        $(outputBox).on('click','li.valid a.search-result',function(){
            $("html,html *").css("cursor","wait"); // Set wait cursor

            var ele = $(this);

            // Pass the request to save the track to the playlist
            requestData = new URLSearchParams({
                'id':               letter_id,                
                'spotify_id':       ele.data('track-id'),
                'cached_title':     decodeURIComponent(ele.data('track-title')),
                'cached_artist':    decodeURIComponent(ele.data('track-artists'))
            });
            var ajaxUpdateOptions = {
                async: true,
                cache: false,
                complete: trackSearch.handleTrackUpdate,
                dataType: 'json',
                method: 'GET',
                timeout: 8000
            };
            $.ajax(root_path+"/ajax/assign_track.php?"+requestData.toString(), ajaxUpdateOptions);
            

            if ('handleSearchClickCustom' in trackSearch) {
                trackSearch.handleSearchClickCustom(ele); // Runs any custom actions for the page on which we're embedding
            }
        });
    });
}