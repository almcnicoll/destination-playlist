if (typeof letterAssigner === 'undefined') { letterAssigner = {}; }

letterAssigner.url = root_path+"/ajax/assign_letters.php?playlist_id="+playlist_id;
letterAssigner.unassignUrl = root_path+"/ajax/unassign_letter.php?letter_id=";

// Swaps an element's first icon for a small spinner while its action is in flight, remembering
// the icon's original class so clearPending can put it back if the action fails
letterAssigner.showPending = function($el) {
    var $icon = $el.find('span').first();
    $el.data('pending-icon-class', $icon.attr('class'));
    $icon.attr('class', 'spinner-border spinner-border-sm');
    $el.addClass('pending');
}
letterAssigner.clearPending = function($el) {
    var origClass = $el.data('pending-icon-class');
    if (origClass) { $el.find('span').first().attr('class', origClass); }
    $el.removeClass('pending');
}

// Returns a `complete` callback bound to whichever element triggered the request (a single row's
// icon for unassign; nothing for the whole-playlist assign/reassign buttons)
letterAssigner.handleActionComplete = function($pendingEl) {
    return function(jqXHR, textStatus) {
        $(letterAssigner.assignButton).prop('disabled',false);
        $(letterAssigner.reassignButton).prop('disabled',false);
        $("html,html *").css("cursor","auto");
        if ($pendingEl) { letterAssigner.clearPending($pendingEl); } // About to be patched with real data on success; unchanged on error either way

        var data = jqXHR.responseJSON;
        if (textStatus === 'success' && data && !data.errors) {
            if ('handleActionResponseCustom' in letterAssigner) {
                // Page knows how to patch just the affected row(s) - let it, and skip the blanket refresh below
                letterAssigner.handleActionResponseCustom(data);
                return;
            }
        } else {
            alert((data && data.errors) ? data.errors.join("\n") : "Error saving letters. Please try again.");
        }

        // Fallback for pages that haven't opted into patching (and always on error, so the UI resyncs with the server)
        clearTimeout(letterGetter.timer);
        letterGetter.getLetters();
    };
}

letterAssigner.ajaxOptions = {
    async: true,
    cache: false,
    dataType: 'json',
    method: 'GET',
    timeout: 4000
};

letterAssigner.init = function(assignButton=null,reassignButton=null) {
    letterAssigner.assignButton = assignButton;
    letterAssigner.reassignButton = reassignButton;
    $(document).ready(
        function () {
            // Assign button
            if (letterAssigner.assignButton!=null) {
                $(letterAssigner.assignButton).on('click',function() {
                    $(letterAssigner.assignButton).prop('disabled',true);
                    $(letterAssigner.reassignButton).prop('disabled',true);
                    $("html, html *").css("cursor","wait");
                    $.ajax(letterAssigner.url, $.extend({}, letterAssigner.ajaxOptions, { complete: letterAssigner.handleActionComplete() }));
                });
            }
            // Reassign button
            if (letterAssigner.reassignButton!=null) {
                $(letterAssigner.reassignButton).on('click',function() {
                    $(letterAssigner.assignButton).prop('disabled',true);
                    $(letterAssigner.reassignButton).prop('disabled',true);
                    $("html, html *").css("cursor","wait");
                    $.ajax(letterAssigner.url+'&from_scratch=true', $.extend({}, letterAssigner.ajaxOptions, { complete: letterAssigner.handleActionComplete() }));
                });
            }
            // Unassign letter icons
            $('body').on('click','a.unassign-letter',function() {
                if ($(this).hasClass('pending')) { return; } // Already saving - ignore repeat clicks
                var $el = $(this);
                letterAssigner.showPending($el); // Spinner on the clicked icon while it saves
                var letter_id = $el.data('letter-id');
                var unassignUrl = letterAssigner.unassignUrl + letter_id;
                $.ajax(unassignUrl, $.extend({}, letterAssigner.ajaxOptions, { complete: letterAssigner.handleActionComplete($el) }));
            })
        }
    );
}