<?php
session_start();

// Hủy session
session_unset();
session_destroy();

// Quay về login admin
header("Location: ../HTML/Admin/admin-login.php");
exit();
?>