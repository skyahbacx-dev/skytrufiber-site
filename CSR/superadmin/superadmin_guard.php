<?php
/* ============================================================
   SUPER ADMIN GUARD
   Resilient check — sets $GLOBALS['CSR_IS_SUPERADMIN'].
   Never throws; defaults to false. Reads the logged-in CSR's
   role from csr_users (works even if the column is missing).
============================================================ */

if (!isset($conn)) {
    require_once __DIR__ . "/../../db_connect.php";
}

$GLOBALS['CSR_IS_SUPERADMIN'] = false;

$__saUser = $_SESSION['csr_user'] ?? '';
if ($__saUser !== '' && isset($conn)) {
    try {
        $col = $conn->query("SELECT 1 FROM information_schema.columns WHERE table_schema='public' AND table_name='csr_users' AND column_name='role' LIMIT 1");
        if ($col && $col->fetch()) {
            $st = $conn->prepare("SELECT role FROM csr_users WHERE username = :u LIMIT 1");
            $st->execute([':u' => $__saUser]);
            $role = strtolower((string)$st->fetchColumn());
            if ($role === 'superadmin' || $role === 'super_admin') {
                $GLOBALS['CSR_IS_SUPERADMIN'] = true;
            }
        }
    } catch (Exception $e) {
        // stay false
    }
}
