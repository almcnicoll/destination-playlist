if (typeof letterAssigner === 'undefined') { letterAssigner = {}; }

letterAssigner.url = root_path+"/ajax/assign_letters.php?playlist_id="+playlist_id;
letterAssigner.unassignUrl = root_path+"/ajax/unassign_letter.php?letter_id=";
letterAssigner.assignableUsersUrl = root_path+"/ajax/get_assignable_users.php?playlist_id="+playlist_id;
letterAssigner.assignToUserUrl = root_path+"/ajax/assign_letter_to_user.php";

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

// Populates the "Assign to..." modal with the owner plus any non-kicked participant, for one
// specific unassigned letter
letterAssigner.openAssignModal = function(letterId, letterChar) {
    $('#assignUserModalLetter').text(letterChar);
    $('#assign-user-list').data('letter-id', letterId);
    $('#assign-user-list').html("<li class='list-group-item fst-italic'>Loading...</li>");
    $.ajax(letterAssigner.assignableUsersUrl, {
        async: true,
        cache: false,
        dataType: 'json',
        method: 'GET',
        timeout: 8000,
        success: function(data) {
            if ('errors' in data) {
                $('#assign-user-list').html("<li class='list-group-item text-danger'>"+data.errors.join(' ')+"</li>");
                return;
            }
            if (data.users.length === 0) {
                $('#assign-user-list').html("<li class='list-group-item fst-italic'>No eligible participants.</li>");
                return;
            }
            var html = '';
            data.users.forEach(function(u) {
                html += "<li class='list-group-item'><a href='#' class='assign-user-option' data-user-id='"+u.id+"'>"
                        +"<span class='bi bi-person-fill'></span> "+u.display_name+"</a></li>";
            });
            $('#assign-user-list').html(html);
        },
        error: function() {
            $('#assign-user-list').html("<li class='list-group-item text-danger'>Could not load participants. Please try again.</li>");
        }
    });
}

// Assigns one specific letter to one specific user, chosen from the modal above
letterAssigner.assignLetterToUser = function(letterId, userId, $pendingEl) {
    letterAssigner.showPending($pendingEl);
    var url = letterAssigner.assignToUserUrl+'?letter_id='+letterId+'&user_id='+userId;
    $.ajax(url, $.extend({}, letterAssigner.ajaxOptions, {
        complete: function(jqXHR, textStatus) {
            letterAssigner.clearPending($pendingEl);
            var data = jqXHR.responseJSON;
            if (textStatus === 'success' && data && data.letter) {
                $('#assignUserModalCloseX').trigger('click');
                if ('handleActionResponseCustom' in letterAssigner) {
                    letterAssigner.handleActionResponseCustom(data); // Patches the row immediately, same as assign/reassign/unassign
                }
            } else {
                alert((data && data.errors) ? data.errors.join(' ') : "Could not assign letter. Please try again.");
            }
        }
    }));
}

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
            // "Assign" button on an unassigned letter - opens the picker modal (data-bs-toggle
            // on the button itself handles actually showing it)
            $('body').on('click','a.assign-to-user',function() {
                var letterId = $(this).data('letter-id');
                var letterChar = $(this).data('letter');
                letterAssigner.openAssignModal(letterId, letterChar);
            })
            // Picking a user from that modal's list
            $('body').on('click','a.assign-user-option',function(e) {
                e.preventDefault();
                if ($(this).hasClass('pending')) { return; } // Already saving - ignore repeat clicks
                var userId = $(this).data('user-id');
                var letterId = $('#assign-user-list').data('letter-id');
                letterAssigner.assignLetterToUser(letterId, userId, $(this));
            })
        }
    );
}