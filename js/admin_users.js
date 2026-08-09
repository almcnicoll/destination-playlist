if (typeof adminUsers === 'undefined') { adminUsers = {}; }

adminUsers.state = {
    search: '',
    sort: 'created',
    dir: 'desc',
    page: 1,
    pageSize: 25,
    total: 0
};

adminUsers.searchTimer = null;

// This table renders admin-viewed, user-controlled strings (display names, emails, Spotify
// identifiers) - unlike the rest of this app's row-builders, this one must escape them, since a
// malicious display name shouldn't be able to run script in an admin's session
adminUsers.escapeHtml = function(str) {
    if (str === null || str === undefined) { return ''; }
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

adminUsers.formatDate = function(str) {
    if (!str) { return ''; }
    return str.replace('T', ' ').substr(0, 16);
}

adminUsers.buildAccountCell = function(u) {
    var method = adminUsers.escapeHtml(u.authmethod_name || '');
    var identifier = adminUsers.escapeHtml(u.identifier || '');
    var badge = "<span class='badge bg-secondary'>"+method+"</span> ";
    if ((u.authmethod_name || '').toLowerCase() === 'spotify' && u.identifier) {
        return badge+"<a href='https://open.spotify.com/user/"+encodeURIComponent(u.identifier)+"' target='_blank' rel='noopener'>"+identifier+"</a>";
    }
    return badge+identifier;
}

adminUsers.buildRowHtml = function(u) {
    var thumb = u.image_url
        ? "<div class='initial-display'><img src='"+adminUsers.escapeHtml(u.image_url)+"' /></div>"
        : "<div class='initial-display'>"+adminUsers.escapeHtml((u.display_name || '?').substr(0,1))+"</div>";
    return "<td>"+u.id+"</td>"
        +"<td>"+thumb+"</td>"
        +"<td>"+adminUsers.escapeHtml(u.display_name)+"</td>"
        +"<td>"+adminUsers.escapeHtml(u.email)+"</td>"
        +"<td>"+adminUsers.buildAccountCell(u)+"</td>"
        +"<td>"+adminUsers.escapeHtml(u.market)+"</td>"
        +"<td>"+adminUsers.formatDate(u.created)+"</td>"
        +"<td>"+adminUsers.formatDate(u.modified)+"</td>"
        +"<td>"
            +"<a href='#' class='edit-user' data-bs-toggle='modal' data-bs-target='#userEditModal' title='Edit'><span class='bi bi-pencil-square'></span></a>&nbsp;"
            +"<a href='#' class='delete-user text-danger' title='Delete'><span class='bi bi-trash'></span></a>"
        +"</td>";
}

adminUsers.renderRows = function(users) {
    var $tbody = $('#users-table tbody');
    $tbody.empty();
    if (users.length === 0) {
        $tbody.append("<tr><td colspan='9' class='fst-italic'>No users found.</td></tr>");
        return;
    }
    users.forEach(function(u) {
        var $row = $("<tr data-user-id='"+u.id+"'></tr>");
        $row.data('user', u); // Full record stashed here so Edit can populate the modal without another round-trip
        $row.html(adminUsers.buildRowHtml(u));
        $tbody.append($row);
    });
}

adminUsers.renderPagination = function() {
    var s = adminUsers.state;
    var totalPages = Math.max(1, Math.ceil(s.total / s.pageSize));
    var start = (s.total === 0) ? 0 : ((s.page - 1) * s.pageSize) + 1;
    var end = Math.min(s.total, s.page * s.pageSize);
    $('#user-page-summary').text('Showing '+start+'-'+end+' of '+s.total);
    $('#user-page-label').text('Page '+s.page+' of '+totalPages);
    $('#user-page-prev').closest('li').toggleClass('disabled', s.page <= 1);
    $('#user-page-next').closest('li').toggleClass('disabled', s.page >= totalPages);
}

adminUsers.updateSortIndicators = function() {
    $('#users-table th.sortable').removeClass('sort-asc sort-desc');
    $('#users-table th[data-sort-field="'+adminUsers.state.sort+'"]').addClass(adminUsers.state.dir === 'asc' ? 'sort-asc' : 'sort-desc');
}

adminUsers.fetchUsers = function() {
    var s = adminUsers.state;
    $.ajax(root_path+'/ajax/admin_get_users.php', {
        async: true,
        cache: false,
        dataType: 'json',
        method: 'GET',
        timeout: 8000,
        data: { search: s.search, sort: s.sort, dir: s.dir, page: s.page, pageSize: s.pageSize },
        success: function(data) {
            if ('errors' in data) {
                $('#users-table tbody').html("<tr><td colspan='9' class='error text-danger'>"+adminUsers.escapeHtml(data.errors.join(', '))+"</td></tr>");
                return;
            }
            s.total = data.total;
            adminUsers.renderRows(data.result);
            adminUsers.renderPagination();
            adminUsers.updateSortIndicators();
        },
        error: function() {
            $('#users-table tbody').html("<tr><td colspan='9' class='error text-danger'>Could not load users. Please try again.</td></tr>");
        }
    });
}

adminUsers.openEditModal = function(u) {
    $('#userEditModalTitle').text(u ? 'Edit user' : 'New user');
    $('#user-edit-error').empty();
    $('#user-edit-id').val(u ? u.id : '');
    $('#user-edit-authmethod').val(u ? u.authmethod_id : $('#user-edit-authmethod option').first().val());
    $('#user-edit-identifier').val(u ? u.identifier : '');
    $('#user-edit-display-name').val(u ? u.display_name : '');
    $('#user-edit-email').val(u ? u.email : '');
    $('#user-edit-market').val(u ? u.market : '');
    $('#user-edit-image-url').val(u ? u.image_url : '');
}

adminUsers.saveUser = function() {
    $('#user-edit-error').empty();
    var $form = $('#user-edit-form');
    if (!$form[0].reportValidity()) { return; } // Native HTML5 validation (required fields etc.)
    var data = $form.serialize();
    $('#user-edit-save').prop('disabled', true);
    $.ajax(root_path+'/ajax/admin_save_user.php', {
        async: true,
        cache: false,
        dataType: 'json',
        method: 'POST',
        timeout: 8000,
        data: data,
        complete: function(jqXHR, textStatus) {
            $('#user-edit-save').prop('disabled', false);
            var resp = jqXHR.responseJSON;
            if (textStatus === 'success' && resp && resp.success) {
                $('#userEditModalCloseX').trigger('click');
                adminUsers.fetchUsers();
            } else {
                var msg = (resp && resp.errors) ? resp.errors.join(' ') : 'Could not save user. Please try again.';
                $('#user-edit-error').html("<div class='alert alert-danger'>"+adminUsers.escapeHtml(msg)+"</div>");
            }
        }
    });
}

adminUsers.deleteUser = function(id, displayName) {
    if (!confirm("Delete "+(displayName || 'this user')+"? This also removes any playlists they own. This cannot be undone.")) { return; }
    $.ajax(root_path+'/ajax/admin_delete_user.php', {
        async: true,
        cache: false,
        dataType: 'json',
        method: 'POST',
        timeout: 8000,
        data: { id: id },
        complete: function(jqXHR, textStatus) {
            var resp = jqXHR.responseJSON;
            if (textStatus === 'success' && resp && resp.success) {
                adminUsers.fetchUsers();
            } else {
                alert((resp && resp.errors) ? resp.errors.join(' ') : 'Could not delete user. Please try again.');
            }
        }
    });
}

adminUsers.init = function() {
    $(document).ready(function() {
        adminUsers.fetchUsers();

        // Sortable column headers
        $('#users-table thead').on('click', 'th.sortable', function() {
            var field = $(this).data('sort-field');
            if (adminUsers.state.sort === field) {
                adminUsers.state.dir = (adminUsers.state.dir === 'asc') ? 'desc' : 'asc';
            } else {
                adminUsers.state.sort = field;
                adminUsers.state.dir = 'asc';
            }
            adminUsers.state.page = 1;
            adminUsers.fetchUsers();
        });

        // Filter box (debounced, so we're not firing a request on every keystroke)
        $('#user-filter').on('keyup', function() {
            clearTimeout(adminUsers.searchTimer);
            var val = $(this).val();
            adminUsers.searchTimer = setTimeout(function() {
                adminUsers.state.search = val;
                adminUsers.state.page = 1;
                adminUsers.fetchUsers();
            }, 300);
        });

        // Page size
        $('#user-page-size').on('change', function() {
            adminUsers.state.pageSize = parseInt($(this).val(), 10);
            adminUsers.state.page = 1;
            adminUsers.fetchUsers();
        });

        // Pagination
        $('#user-page-prev').on('click', function(e) {
            e.preventDefault();
            if (adminUsers.state.page > 1) { adminUsers.state.page--; adminUsers.fetchUsers(); }
        });
        $('#user-page-next').on('click', function(e) {
            e.preventDefault();
            var totalPages = Math.max(1, Math.ceil(adminUsers.state.total / adminUsers.state.pageSize));
            if (adminUsers.state.page < totalPages) { adminUsers.state.page++; adminUsers.fetchUsers(); }
        });

        // New user
        $('#btn-new-user').on('click', function() {
            adminUsers.openEditModal(null);
        });

        // Edit user (delegated - rows are rebuilt on every fetch)
        $('#users-table').on('click', 'a.edit-user', function(e) {
            e.preventDefault();
            var u = $(this).closest('tr').data('user');
            adminUsers.openEditModal(u);
        });

        // Delete user (delegated)
        $('#users-table').on('click', 'a.delete-user', function(e) {
            e.preventDefault();
            var u = $(this).closest('tr').data('user');
            adminUsers.deleteUser(u.id, u.display_name);
        });

        // Save (create or update)
        $('#user-edit-save').on('click', function() {
            adminUsers.saveUser();
        });
    });
}
