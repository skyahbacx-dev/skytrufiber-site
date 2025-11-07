<?php
session_start();
session_destroy();
header("Location: csr_login.php");
exit;
?>
