<pre>
<?php
$page_parts = explode('/', $_GET['params']);
$params = [];
print_r($page_parts);
// If no params, redirect to index
$get_back = '../../';
if (count($page_parts)==0) {
    header('Location: ./');
    die();
} elseif (count($page_parts)==1) {
    $page_parts[] = 'index';
    $get_back = '../';
} elseif (count($page_parts)>2) {
    // Move extraneous page parts to params 
    while (count($page_parts)>2) {
        array_unshift($params, (array_shift($page_parts)) );
    }
}
$page = "pages/{$page_parts[0]}_{$page_parts[1]}.php";
// TODO - include that file here
?>
</pre>