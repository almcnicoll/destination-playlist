    var letterHash = '';
    var search_letter = '';
    var letter_id = null;
    var timer2;
    var script2Url = root_path+"/ajax/get_letters.php?playlist_id="+playlist_id;
    function updateLetters(data, textStatus, jqXHR) {
        // Check hash
        if (data.hash == letterHash) { return; } // Result's the same - don't bother doing anything
        letterHash = data.hash;
        $('#tracks-table tbody tr').remove();
        // Manage errors or good data
        if ('errors' in data) {            
            $('#tracks-table tbody tr').remove();
            for(var i in data.errors) {
                $('#tracks-table tbody').append("<tr><td class='error'><div class='error'>"+data.errors[i]+"</td></tr>");
            }
        } else {
            letterData = data.result;
            for(var i in letterData) {
                var l = letterData[i];
                var user_display = "";
                var edit_own = "";
                if ((letterData[i].user_id != null) && (letterData[i].user_id != 'null')) {
                    var u = letterData[i].user;
                    user_display = "<div class='initial-display'>"+u.display_name.substr(0,1)+"</div>";
                    if (u.id == currentUser) {
                        edit_own = "<a href='#' id='edit-track-"+i+"'  class='btn' data-bs-toggle='modal' data-bs-target='#trackSearchModal' onclick=\"search_letter = '"+l.letter.toUpperCase()+"'; letter_id = "+l.id+";\"><span class='bi bi-pencil-square'></span></a>";
                    }
                }
                $('#tracks-table tbody').append("<tr><td class='letter-display'><div class='letter-display'>"+l.letter.toUpperCase()+"</div></td><td>"+l.cached_title+"</td><td>"+l.cached_artist+"</td><td class='initial-display'>"+user_display+"</td><td>"+edit_own+"</td></tr>");
            }
        }
    }
    var ajax2Options = {
        async: true,
        cache: false,
        success: updateLetters,
        dataType: 'json',
        method: 'GET',
        timeout: 8000
    };
    function getLetters() {
        $.ajax(script2Url, ajax2Options);
        timer2 = setTimeout('getLetters()',10000);
    }