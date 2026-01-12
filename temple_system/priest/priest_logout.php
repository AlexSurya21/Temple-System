<?php
session_start();
session_unset();
session_destroy();
header("Location: priest_login.php");
exit;
?>
