<?php
// Shared connection + helpers. Read-only demo — never writes to the database.

function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli('localhost', 'root', '', 'total_cargo');
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function v($val, $fallback = '—') {
    if ($val === null || $val === '') return $fallback;
    return htmlspecialchars((string) $val, ENT_QUOTES);
}
function d($val) {
    if (!$val || $val === '0000-00-00') return '';
    $t = strtotime($val);
    return $t ? date('d/m/Y', $t) : '';
}
function money($val) {
    if ($val === null || $val === '') return '0';
    return number_format((float) $val, 2);
}
function esc($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
