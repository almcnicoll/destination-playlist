if (typeof letterAssigner === 'undefined') { letterAssigner = {}; }    

letterAssigner.url = root_path+"/ajax/assign_letters.php?playlist_id="+playlist_id;
letterAssigner.unassignUrl = root_path+"/ajax/unassign_letter.php?letter_id=";

letterAssigner.updateLettersNow = function() {
    // Refresh immediately
    clearTimeout(letterGetter.timer);
    letterGetter.getLetters();
    $("html,html *").css("cursor","auto");
}

letterAssigner.ajaxOptions = {
    async: true,
    cache: false,
    dataType: 'json',
    method: 'GET',
    timeout: 4000,
    complete: letterAssigner.updateLettersNow
};

letterAssigner.init = function(target) {
    $(document).ready(
        function () {
            $(target).on('click',function() {
                $("html, html *").css("cursor","wait");
                $.ajax(letterAssigner.url, letterAssigner.ajaxOptions);
            });
            $('body').on('click','a.unassign-letter',function() {
                $("html, html *").css("cursor","wait");
                var letter_id = $(this).data('letter-id');
                var unassignUrl = letterAssigner.unassignUrl + letter_id;
                $.ajax(unassignUrl, letterAssigner.ajaxOptions);
            })
        }
    );
}