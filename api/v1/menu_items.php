<?php
require_once __DIR__ . "/_helpers.php";
require_method("GET");

$all = get_int("all", 0);

if ($all === 1) {
    require_admin();
    $result = $con->query("SELECT id, name, url, visible FROM menu_items ORDER BY id ASC");
} else {
    $result = $con->query("SELECT id, name, url, visible FROM menu_items WHERE visible=1 ORDER BY id ASC");
}

$data = [];
while ($r = $result->fetch_assoc()) $data[] = $r;

json_response(["status"=>"success","data"=>$data]);
