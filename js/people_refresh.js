if (typeof peopleGetter === 'undefined') { peopleGetter = {}; }
peopleGetter.url = root_path+"/ajax/get_participants.php?playlist_id="+playlist_id;

peopleGetter.updatePeopleList = function(data, textStatus, jqXHR) {
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
peopleGetter.getParticipants = function() {
    $.ajax(peopleGetter.url, peopleGetter.ajaxOptions);
    peopleGetter.timer = setTimeout('peopleGetter.getParticipants()',peopleGetter.frequency);
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
        }
    );
}