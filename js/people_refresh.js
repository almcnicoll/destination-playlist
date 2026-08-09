if (typeof peopleGetter === 'undefined') { peopleGetter = {}; }
peopleGetter.peopleHash = ''; // Tracks the last-seen result hash, mirroring letterGetter, so an unchanged poll skips re-rendering
peopleGetter.url = root_path+"/ajax/get_participants.php?playlist_id="+playlist_id;
peopleGetter.kickUrl = root_path+"/ajax/kick_participant.php?playlist_id="+playlist_id+"&user_id=";

peopleGetter.updatePeopleList = function(data, textStatus, jqXHR) {
    // Check hash - result's the same, don't bother re-rendering (mirrors letterGetter)
    if (data.hash == peopleGetter.peopleHash) { return; }
    peopleGetter.peopleHash = data.hash;

    if ('updatePeopleListCustom' in peopleGetter) {peopleGetter.updatePeopleListCustom(data, textStatus, jqXHR); }
}
peopleGetter.ajaxOptions = {
    async: true,
    cache: false,
    success: peopleGetter.updatePeopleList,
    dataType: 'json',
    method: 'GET',
    timeout: peopleGetter.timeout
};
peopleGetter.kickAjaxOptions = {
    async: true,
    cache: false,
    dataType: 'json',
    method: 'GET',
};
peopleGetter.getParticipants = function() {
    $.ajax(peopleGetter.url, peopleGetter.ajaxOptions);
    peopleGetter.timer = setTimeout('peopleGetter.getParticipants()',peopleGetter.frequency);
}
// Returns a `complete` callback bound to the row that triggered the request, so the wait cursor
// it set on click always gets cleared and the row is patched with the confirmed result
peopleGetter.handleKickResponse = function($row) {
    return function(jqXHR, textStatus) {
        $row.css('cursor','auto');
        if (textStatus === 'success' && jqXHR.responseJSON && jqXHR.responseJSON.participant) {
            if ('patchParticipantsCustom' in peopleGetter) {
                peopleGetter.patchParticipantsCustom([jqXHR.responseJSON.participant], false);
            }
        } else {
            alert("Error updating participant. Please try again.");
            // We don't know the row's true state any more - resync from the server
            clearTimeout(peopleGetter.timer);
            peopleGetter.getParticipants();
        }
    };
}
peopleGetter.unkickParticipant = function() {
    var uid = $(this).data('user-id');
    var $row = $(this).closest('tr');
    $(this).css('cursor','wait');
    $row.css('cursor','wait');
    $.ajax(peopleGetter.kickUrl+uid+"&kick=false", $.extend({}, peopleGetter.kickAjaxOptions, { complete: peopleGetter.handleKickResponse($row) }));
}
peopleGetter.kickParticipant = function() {
    var uid = $(this).data('user-id');
    var $row = $(this).closest('tr');
    $(this).css('cursor','wait');
    $row.css('cursor','wait');
    $.ajax(peopleGetter.kickUrl+uid+"&kick=true", $.extend({}, peopleGetter.kickAjaxOptions, { complete: peopleGetter.handleKickResponse($row) }));
}
peopleGetter.init = function(initialDelay, frequency, timeout) {
    peopleGetter.initialDelay = initialDelay;
    peopleGetter.frequency = frequency;
    peopleGetter.timeout = timeout;

    $(document).ready(
        function () {
            if (peopleGetter.initialDelay==0) {
                peopleGetter.getParticipants();
            } else {
                peopleGetter.timer = setTimeout('peopleGetter.getParticipants()',peopleGetter.initialDelay);
            }

            // Handle kick-user
            $('#people-table').on('click','td.kick-user a',peopleGetter.kickParticipant);
            // Handle unkick-user
            $('#people-table').on('click','td.unkick-user a',peopleGetter.unkickParticipant);
        }
    );
}