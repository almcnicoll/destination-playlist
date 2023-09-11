
    var timer2;
    var script2Url = root_path+"/ajax/get_letters.php?playlist_id="+playlist_id;
    function updateTrackList(data, textStatus, jqXHR) {
        $('#tracks-table tbody tr').remove();
        for(var i in data) {
            var l = data[i];
            var user_display = "";
            var edit_own = "";
            if ((data[i].user_id != null) && (data[i].user_id != 'null')) {
                var u = data[i].user;
                user_display = "<div class='initial-display'>"+u.display_name.substr(0,1)+"</div>";
                if (u.id == currentUser) {
                    edit_own = "<a href='#' id=''><span class='bi bi-pencil-square'></span></a>";
                }
            }
            $('#tracks-table tbody').append("<tr><td class='letter-display'><div class='letter-display'>"+l.letter.toUpperCase()+"</div></td><td>"+l.cached_title+"</td><td>"+l.cached_artist+"</td><td class='initial-display'>"+user_display+"</td><td>"+edit_own+"</td></tr>");
        }
    }
    var ajax2Options = {
        async: true,
        cache: false,
        success: updateTrackList,
        dataType: 'json',
        method: 'GET',
        timeout: 8000
    };
    function getLetters() {
        $.ajax(script2Url, ajax2Options);
        timer2 = setTimeout('getLetters()',10000);
    }