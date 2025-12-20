<?php
require_once __DIR__ . '/db_connect_mysql.php';
require_once __DIR__ . '/db_connect_neon.php';

/**
 * READ — MySQL only (fast, stable)
 */
function db_select(string $sql, array $params = []) {
    global $pdo_mysql;
    $stmt = $pdo_mysql->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * WRITE — MySQL + Neon (dual-write)
 */
function db_execute(string $sql, array $params = []) {
    global $pdo_mysql, $pdo_neon;

    try {
        // Always write to MySQL
        $stmtMy = $pdo_mysql->prepare($sql);
        $stmtMy->execute($params);

        // Also write to Neon if available
        if ($pdo_neon) {
            $stmtPg = $pdo_neon->prepare($sql);
            $stmtPg->execute($params);
        }

        return true;

    } catch (PDOException $e) {
        error_log("DB WRITE ERROR: " . $e->getMessage());
        return false;
    }
}
function db_last_insert_id() {
    global $pdo_mysql;
    return $pdo_mysql->lastInsertId();
}
