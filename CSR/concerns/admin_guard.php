<?php
/* ============================================================
   ADMIN GUARD  —  who can see the "All Concerns" console
   ------------------------------------------------------------
   Add the username(s) of your supervisor/admin CSR account(s)
   to the list below. Only these logins will see the button and
   be allowed to open the console.

   Example:
     $CSR_ADMIN_USERS = ['admin', 'supervisor_jen'];
   ============================================================ */

$CSR_ADMIN_USERS = ['AHBA_CSR01'];

$__csrUser = $_SESSION['csr_user'] ?? '';
$__inWhitelist = ($__csrUser !== '' && in_array($__csrUser, $CSR_ADMIN_USERS, true));

/* Super admins are automatically All Concerns admins too */
require_once __DIR__ . "/../superadmin/superadmin_guard.php";

$GLOBALS['CSR_IS_ADMIN'] = ($__inWhitelist || !empty($GLOBALS['CSR_IS_SUPERADMIN']));
