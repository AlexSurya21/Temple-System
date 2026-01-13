<?php
session_start();
session_unset();
session_destroy();
// Back to main home page
header("Location: ../index.php");  
exit();

?>
