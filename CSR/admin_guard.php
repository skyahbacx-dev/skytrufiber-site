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

$CSR_ADMIN_USERS = ['admin'];

$__csrUser = $_SESSION['csr_user'] ?? '';
$GLOBALS['CSR_IS_ADMIN'] = ($__csrUser !== '' && in_array($__csrUser, $CSR_ADMIN_USERS, true));
