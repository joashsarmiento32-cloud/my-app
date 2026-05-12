<?php
session_start();
session_unset(); // Removes all session variables
session_destroy(); // Destroys the session completely

// Redirect back to your login page (change to index.php if that's your login)
header("Location: admin_login.php");
exit();
?>