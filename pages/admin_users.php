<?php
    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    $user = $_SESSION['USER'];

    // Same gate as admin_dashboard.php - single hardcoded admin account, no roles table yet
    if (!$user->isAdmin()) {
        header("Location: ../");
        die();
    }

    $authmethods = AuthMethod::getAll();
?>
<script type="text/javascript">
    var root_path = "<?= $config['root_path'] ?>";
    var currentUserId = <?= $_SESSION['USER_ID'] ?>;
</script>
<script type='text/javascript' src='<?= $config['root_path'] ?>/js/admin_users.js?<?= $config['js_version'] ?>'></script>
<script type='text/javascript'>
    adminUsers.init();
</script>

<div class='top-left-menu'><a href="<?= $config['root_path'] ?>/admin/dashboard" class='btn btn-warning btn-md'><< Back</a></div>
<h2 class="text-center">Manage Users</h2>
<?php
if (count($error_messages)>0) {
    foreach($error_messages as $error_message) {
?>
<div class="row">
    <div class="span12 alert alert-danger"><?= $error_message ?></div>
</div>
<?php
    }
}
?>

<div class="row mb-2 align-items-end">
    <div class="col-12 col-md-6">
        <label for="user-filter" class="form-label">Filter</label>
        <input type="text" class="form-control" id="user-filter" placeholder="Search name, email or Spotify account...">
    </div>
    <div class="col-6 col-md-3">
        <label for="user-page-size" class="form-label">Per page</label>
        <select class="form-select" id="user-page-size">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>
    <div class="col-6 col-md-3 text-end">
        <button class="btn btn-success" id="btn-new-user" data-bs-toggle="modal" data-bs-target="#userEditModal">+ New user</button>
    </div>
</div>

<table class="table table-light table-striped neat" id="users-table">
    <thead>
        <tr>
            <th data-sort-field="id" class="sortable">ID</th>
            <th>&nbsp;</th>
            <th data-sort-field="display_name" class="sortable">Name</th>
            <th data-sort-field="email" class="sortable">Email</th>
            <th data-sort-field="authmethod" class="sortable">Linked account</th>
            <th data-sort-field="market" class="sortable">Market</th>
            <th data-sort-field="created" class="sortable">Created</th>
            <th data-sort-field="modified" class="sortable">Modified</th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class='loading-cell' colspan="9">Loading...</td>
        </tr>
    </tbody>
</table>

<div class="row">
    <div class="col-12 col-md-6" id="user-page-summary"></div>
    <div class="col-12 col-md-6">
        <nav>
            <ul class="pagination justify-content-md-end" id="user-pagination">
                <li class="page-item"><a class="page-link" href="#" id="user-page-prev">&laquo; Prev</a></li>
                <li class="page-item disabled"><span class="page-link" id="user-page-label">Page 1</span></li>
                <li class="page-item"><a class="page-link" href="#" id="user-page-next">Next &raquo;</a></li>
            </ul>
        </nav>
    </div>
</div>

<div class="modal fade" id="userEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userEditModalTitle">New user</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="userEditModalCloseX"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-1">
                    <div class="col-12" id="user-edit-error"></div>
                </div>
                <form id="user-edit-form">
                    <input type="hidden" id="user-edit-id" name="id">
                    <div class="mb-2">
                        <label for="user-edit-authmethod" class="form-label">Login method</label>
                        <select class="form-select" id="user-edit-authmethod" name="authmethod_id" required>
                            <?php foreach ($authmethods as $am): ?>
                            <option value="<?= $am->id ?>"><?= htmlspecialchars($am->methodName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="user-edit-identifier" class="form-label">Account identifier</label>
                        <input type="text" class="form-control" id="user-edit-identifier" name="identifier" required>
                        <div class="form-text">The account's Spotify user ID (or equivalent for the chosen login method).</div>
                    </div>
                    <div class="mb-2">
                        <label for="user-edit-display-name" class="form-label">Display name</label>
                        <input type="text" class="form-control" id="user-edit-display-name" name="display_name">
                    </div>
                    <div class="mb-2">
                        <label for="user-edit-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="user-edit-email" name="email">
                    </div>
                    <div class="mb-2">
                        <label for="user-edit-market" class="form-label">Market</label>
                        <input type="text" class="form-control" id="user-edit-market" name="market" maxlength="2" placeholder="GB">
                    </div>
                    <div class="mb-2">
                        <label for="user-edit-image-url" class="form-label">Image URL</label>
                        <input type="text" class="form-control" id="user-edit-image-url" name="image_url">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="user-edit-save">Save</button>
            </div>
        </div>
    </div>
</div>
