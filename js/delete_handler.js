var idToDelete = null;
function deletePlaylist(deleteLocal, deleteFromSpotify) {
    // Do stuff
}

$(document).ready( function() {
    $('#deleteHere').click( function() { deletePlaylist(true,false); } );
    $('#deleteBoth').click( function() { deletePlaylist(true,true); } );
} );