if (typeof letterAssigner === 'undefined') { letterAssigner = {}; }    

letterAssigner.url = root_path+"/ajax/assign_letters.php?playlist_id="+playlist_id;
letterAssigner.ajaxOptions = {
    async: true,
    cache: false,
    dataType: 'json',
    method: 'GET',
    timeout: 4000
};

letterAssigner.init = function(target) {
    $(document).ready(
        function () {
            $(target).on('click',function() {
                $.ajax(letterAssigner.url, letterAssigner.ajaxOptions);
            });
        }
    );
}