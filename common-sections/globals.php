<?php
if (!defined('COMMON_SECTIONS_GLOBALS_LOADED')) {
    define('COMMON_SECTIONS_GLOBALS_LOADED', true);

    $global_db_host = "sql300.byethost18.com";
    $global_db_user = "b18_41230477";
    $global_db_pass = "Wateva06@";
    $global_db_name = "b18_41230477_db";

    $globalConn = null;

    if (isset($conn) && $conn instanceof mysqli && empty($conn->connect_error)) {
        $globalConn = $conn;
    } elseif (isset($dbconn) && $dbconn instanceof mysqli && empty($dbconn->connect_error)) {
        $globalConn = $dbconn;
    } else {
        $globalConn = new mysqli($global_db_host, $global_db_user, $global_db_pass, $global_db_name);
        if (!empty($globalConn->connect_error)) {
            die("Connection failed: " . $globalConn->connect_error);
        }
    }

    $conn = $globalConn;
    $dbconn = $globalConn;

    if (!function_exists('asset_url')) {
        function asset_url(string $path): string {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $path;
            if (file_exists($filePath)) {
                $separator = (strpos($path, '?') === false) ? '?' : '&';
                return $path . $separator . 'v=' . filemtime($filePath);
            }
            return $path;
        }
    }
}
?>
