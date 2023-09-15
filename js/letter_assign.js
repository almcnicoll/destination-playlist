if (typeof letterAssigner === 'undefined') { letterAssigner = {}; }    

letterAssigner.url = root_path+"/ajax/assign_letters.php?playlist_id="+playlist_id;

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
        }
    );
}