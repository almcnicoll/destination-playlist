if (typeof swapMarket === 'undefined') { swapMarket = {}; }

swapMarket.offerUrl = root_path+"/ajax/offer_letter_swap.php";
swapMarket.marketUrl = root_path+"/ajax/get_swap_market.php?playlist_id="+playlist_id;
swapMarket.proposeUrl = root_path+"/ajax/propose_swap.php";
swapMarket.cancelUrl = root_path+"/ajax/cancel_swap_proposal.php";
swapMarket.respondUrl = root_path+"/ajax/respond_swap_proposal.php";
swapMarket.withdrawUrl = root_path+"/ajax/withdraw_swap_offer.php?playlist_id="+playlist_id;
// Abandoning is just a plain unassign (same endpoint the old row-level x used) - see the comment
// in ajax/unassign_letter.php for why that's also what takes it off the swap market
swapMarket.abandonUrl = root_path+"/ajax/unassign_letter.php?letter_id=";

swapMarket.pollTimer = null;
swapMarket.actionInFlight = false; // Guards propose/cancel/respond against double-clicks
swapMarket.hadOffer = false; // Did our last poll see us with an active offer? Lets us tell "the offer
                              // vanished because a swap completed" apart from "we withdrew it ourselves"
                              // (withdrawal always stops polling first, so no poll can land after it)

// Shown on the owner's own row for an already-assigned letter - opens the modal and puts that
// letter up for swap in one action
swapMarket.buildSwapButtonHtml = function(letterId, letterChar) {
    return "<a href='#' class='btn btn-sm btn-outline-warning offer-for-swap' data-bs-toggle='modal' data-bs-target='#swapMarketModal' "
         + "data-letter-id='"+letterId+"' data-letter='"+letterChar+"' title='Offer to swap this letter'><span class='bi bi-arrow-left-right'></span></a>";
}

// Patches the main tracks table from a swap response if this page supports it (playlist_manage.php
// does; playlist_join.php still rebuilds its whole table per poll, so it just falls back to that)
swapMarket.reflectLetters = function(letters) {
    if ('patchLetters' in letterGetter) {
        letterGetter.patchLetters(letters, false);
    } else {
        clearTimeout(letterGetter.timer);
        letterGetter.getLetters();
    }
}

swapMarket.startOffer = function(letterId, letterChar) {
    swapMarket.hadOffer = false;
    $('#swapMarketModal').data('letter-id', letterId); // Remembered for the "Abandon letter" button
    $('#swapMarketModalLetter').text(letterChar);
    $('#swap-market-body').html("<p class='fst-italic'>Putting your letter up for swap...</p>");
    $.ajax(swapMarket.offerUrl+'?letter_id='+letterId, {
        async: true, cache: false, dataType: 'json', method: 'GET', timeout: 8000,
        complete: function(jqXHR, textStatus) {
            var data = jqXHR.responseJSON;
            if (textStatus === 'success' && data && data.success) {
                swapMarket.pollNow();
                swapMarket.pollTimer = setInterval(swapMarket.pollNow, 1000);
            } else {
                $('#swap-market-body').html("<p class='text-danger'>"+((data && data.errors) ? data.errors.join(' ') : 'Could not start the swap.')+"</p>");
            }
        }
    });
}

swapMarket.pollNow = function() {
    $.ajax(swapMarket.marketUrl, {
        async: true, cache: false, dataType: 'json', method: 'GET', timeout: 8000,
        success: function(data) {
            if ('errors' in data) {
                $('#swap-market-body').html("<p class='text-danger'>"+data.errors.join(' ')+"</p>");
                return;
            }
            swapMarket.render(data);
        }
        // Deliberately no error handler that stops the interval - one missed tick (a brief network
        // blip) shouldn't end the session; the next tick a second later just tries again
    });
}

swapMarket.render = function(data) {
    if (swapMarket.hadOffer && !data.myOfferedLetter) {
        // Our offer disappeared while we were still actively polling (withdrawing always stops
        // polling first, so that's not what happened here) - the only other way is a completed swap
        swapMarket.stopPolling();
        $('#swap-market-body').html("<p class='text-success'><span class='bi bi-check-circle'></span> Swap complete!</p>");
        clearTimeout(letterGetter.timer);
        letterGetter.getLetters(); // Reflect the new ownership in the main table right away
        setTimeout(function() { $('#swapMarketModalCloseX').trigger('click'); }, 1500);
        return;
    }
    swapMarket.hadOffer = !!data.myOfferedLetter;

    var html = '';

    if (data.myProposalTarget) {
        html += "<div class='alert alert-info'>Waiting for <strong>"+data.myProposalTarget.display_name+"</strong> to accept swapping "
              + "their <strong>"+data.myProposalTarget.letter+"</strong> for your letter&hellip; "
              + "<a href='#' class='cancel-proposal' data-letter-id='"+data.myOfferedLetter.letter_id+"'>Cancel</a></div>";
    }

    if (data.incomingProposals && data.incomingProposals.length > 0) {
        data.incomingProposals.forEach(function(p) {
            html += "<div class='alert alert-warning'><strong>"+p.display_name+"</strong> wants to swap their <strong>"+p.letter+"</strong> for your letter. "
                  + "<a href='#' class='btn btn-sm btn-success respond-proposal' data-proposer-letter-id='"+p.letter_id+"' data-action='accept'>Accept</a> "
                  + "<a href='#' class='btn btn-sm btn-outline-secondary respond-proposal' data-proposer-letter-id='"+p.letter_id+"' data-action='decline'>Decline</a></div>";
        });
    }

    if (data.myProposalTarget) {
        // Only one outgoing proposal at a time - cancel it above before browsing other offers
        html += "<p class='fst-italic text-muted'>Cancel your pending swap above to browse other offers.</p>";
    } else if (!data.market || data.market.length === 0) {
        html += "<p class='fst-italic'>No one else has a letter up for swap right now.</p>";
    } else {
        html += "<ul class='list-group'>";
        data.market.forEach(function(m) {
            html += "<li class='list-group-item'><a href='#' class='propose-swap' data-target-letter-id='"+m.letter_id+"'>"
                  + "<span class='badge bg-primary'>"+m.letter+"</span> offered by "+m.display_name+"</a></li>";
        });
        html += "</ul>";
    }

    $('#swap-market-body').html(html);
}

swapMarket.stopPolling = function() {
    if (swapMarket.pollTimer) {
        clearInterval(swapMarket.pollTimer);
        swapMarket.pollTimer = null;
    }
}

swapMarket.withdraw = function() {
    swapMarket.stopPolling();
    $.ajax(swapMarket.withdrawUrl, { async: true, cache: false, dataType: 'json', method: 'GET', timeout: 8000 });
}

// Gives up the letter currently open in the modal entirely - not just taking it off the swap
// market (that's withdraw, above), but unassigning it so it's free for anyone to claim
swapMarket.abandonLetter = function() {
    if (swapMarket.actionInFlight) { return; }
    var letterId = $('#swapMarketModal').data('letter-id');
    if (!letterId) { return; }
    swapMarket.actionInFlight = true;
    swapMarket.stopPolling(); // We're about to leave the modal either way; no point polling mid-request
    $.ajax(swapMarket.abandonUrl+letterId, {
        async: true, cache: false, dataType: 'json', method: 'GET', timeout: 8000,
        complete: function(jqXHR, textStatus) {
            swapMarket.actionInFlight = false;
            var data = jqXHR.responseJSON;
            if (textStatus === 'success' && data && data.letter) {
                swapMarket.reflectLetters([data.letter]);
                $('#swapMarketModalCloseX').trigger('click'); // Nothing left to do here - the letter isn't ours any more
            } else {
                alert((data && data.errors) ? data.errors.join(' ') : "Could not abandon that letter. Please try again.");
                // We're still offering it (the abandon never went through) - resume as before
                swapMarket.pollNow();
                swapMarket.pollTimer = setInterval(swapMarket.pollNow, 1000);
            }
        }
    });
}

swapMarket.init = function() {
    $(document).ready(function() {
        // Opening the "offer for swap" button
        $('body').on('click', 'a.offer-for-swap', function() {
            var letterId = $(this).data('letter-id');
            var letterChar = $(this).data('letter');
            swapMarket.startOffer(letterId, letterChar);
        });

        // Tapping someone else's offer proposes a swap
        $('body').on('click', 'a.propose-swap', function(e) {
            e.preventDefault();
            if (swapMarket.actionInFlight) { return; }
            swapMarket.actionInFlight = true;
            var targetLetterId = $(this).data('target-letter-id');
            $.ajax(swapMarket.proposeUrl+'?target_letter_id='+targetLetterId, {
                async: true, cache: false, dataType: 'json', method: 'GET', timeout: 8000,
                complete: function(jqXHR, textStatus) {
                    swapMarket.actionInFlight = false;
                    var data = jqXHR.responseJSON;
                    if (!(textStatus === 'success' && data && data.success)) {
                        alert((data && data.errors) ? data.errors.join(' ') : "Could not propose that swap. Please try again.");
                    }
                    swapMarket.pollNow(); // Refresh immediately either way
                }
            });
        });

        // Cancelling my own outgoing proposal
        $('body').on('click', 'a.cancel-proposal', function(e) {
            e.preventDefault();
            if (swapMarket.actionInFlight) { return; }
            swapMarket.actionInFlight = true;
            var letterId = $(this).data('letter-id');
            $.ajax(swapMarket.cancelUrl+'?letter_id='+letterId, {
                async: true, cache: false, dataType: 'json', method: 'GET', timeout: 8000,
                complete: function() {
                    swapMarket.actionInFlight = false;
                    swapMarket.pollNow();
                }
            });
        });

        // Accepting or declining an incoming proposal
        $('body').on('click', 'a.respond-proposal', function(e) {
            e.preventDefault();
            if (swapMarket.actionInFlight) { return; }
            swapMarket.actionInFlight = true;
            var proposerLetterId = $(this).data('proposer-letter-id');
            var action = $(this).data('action');
            $.ajax(swapMarket.respondUrl+'?proposer_letter_id='+proposerLetterId+'&action='+action, {
                async: true, cache: false, dataType: 'json', method: 'GET', timeout: 8000,
                complete: function(jqXHR, textStatus) {
                    swapMarket.actionInFlight = false;
                    var data = jqXHR.responseJSON;
                    if (textStatus === 'success' && data && data.success) {
                        if (data.letters) { swapMarket.reflectLetters(data.letters); }
                    } else {
                        alert((data && data.errors) ? data.errors.join(' ') : "Could not respond to that swap request.");
                    }
                    swapMarket.pollNow();
                }
            });
        });

        // "Abandon letter" - gives up the letter entirely, not just taking it off the market
        $('body').on('click', '#swap-abandon-letter', function(e) {
            e.preventDefault();
            swapMarket.abandonLetter();
        });

        // Closing the modal - X, Cancel, backdrop click, or Escape all fire this one event - takes
        // our letter off the market
        $('#swapMarketModal').on('hidden.bs.modal', function() {
            swapMarket.withdraw();
        });

        // Best-effort cleanup if the tab is closed outright instead of the modal being closed properly.
        // If this doesn't fire (crash, killed process), the offer just goes stale within a few seconds
        // instead - see the staleness check in ajax/get_swap_market.php
        window.addEventListener('beforeunload', function() {
            if (swapMarket.pollTimer) {
                navigator.sendBeacon(swapMarket.withdrawUrl);
            }
        });
    });
}
