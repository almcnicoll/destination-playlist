<?php
    require_once('../autoload.php');
    // Returns a filtered/sorted/paginated slice of users for the admin Users page
    ob_start();

    User::loginCheck(false);

    $fatal_error = false;
    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    if (!$_SESSION['USER']->isAdmin()) {
        $error_messages[] = "You do not have access to this area.";
        $fatal_error = true;
    }

    if ($fatal_error) {
        $output = json_encode(['errors' => $error_messages]);
        http_response_code(403);
        ob_end_clean();
        die($output);
    }

    // Whitelist of sortable columns - the sort field comes straight from the querystring, so it must
    // never be interpolated into the SQL directly
    $sortableColumns = [
        'id'           => 'u.id',
        'display_name' => 'u.display_name',
        'email'        => 'u.email',
        'identifier'   => 'u.identifier',
        'market'       => 'u.market',
        'authmethod'   => 'am.methodName',
        'created'      => 'u.created',
        'modified'     => 'u.modified',
    ];
    $sortField = $_REQUEST['sort'] ?? 'created';
    if (!array_key_exists($sortField, $sortableColumns)) {
        $sortField = 'created';
    }
    $sortColumn = $sortableColumns[$sortField];
    $sortDir = (isset($_REQUEST['dir']) && strtoupper($_REQUEST['dir']) === 'ASC') ? 'ASC' : 'DESC';

    $page = max(1, (int)($_REQUEST['page'] ?? 1));
    $pageSize = (int)($_REQUEST['pageSize'] ?? 25);
    if ($pageSize < 5) { $pageSize = 5; }
    if ($pageSize > 200) { $pageSize = 200; }
    $offset = ($page - 1) * $pageSize;

    $search = trim($_REQUEST['search'] ?? '');

    $pdo = db::getPDO();

    $whereSql = '';
    $params = [];
    if ($search !== '') {
        // Native prepares (see class/db.php) don't allow reusing one named placeholder multiple
        // times in a query, so each LIKE gets its own, all bound to the same value
        $whereSql = "WHERE (u.display_name LIKE :search1 OR u.email LIKE :search2 OR u.identifier LIKE :search3 OR am.methodName LIKE :search4)";
        $searchTerm = '%'.$search.'%';
        $params['search1'] = $searchTerm;
        $params['search2'] = $searchTerm;
        $params['search3'] = $searchTerm;
        $params['search4'] = $searchTerm;
    }

    $sqlCount = "SELECT COUNT(*) AS c FROM users u LEFT JOIN authmethods am ON am.id = u.authmethod_id {$whereSql}";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total = (int)$stmtCount->fetch(PDO::FETCH_ASSOC)['c'];

    // page/pageSize/offset are cast to int above, so interpolating them into LIMIT/OFFSET is safe
    $sqlList = "SELECT u.*, am.methodName AS authmethod_name
                FROM users u
                LEFT JOIN authmethods am ON am.id = u.authmethod_id
                {$whereSql}
                ORDER BY {$sortColumn} {$sortDir}
                LIMIT {$pageSize} OFFSET {$offset}";
    $stmtList = $pdo->prepare($sqlList);
    $stmtList->execute($params);
    $stmtList->setFetchMode(PDO::FETCH_CLASS, 'User');
    $users = $stmtList->fetchAll();

    $output = [
        'result'   => $users,
        'total'    => $total,
        'page'     => $page,
        'pageSize' => $pageSize,
    ];
    ob_end_clean();
    die(json_encode($output));
