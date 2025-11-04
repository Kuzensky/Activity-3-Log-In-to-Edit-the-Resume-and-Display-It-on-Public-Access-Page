<?php
// Start the session to access session variables
session_start();
// Clear all session variables by setting the session array to empty
$_SESSION = array();
// Destroy the session completely
session_destroy();
// Redirect user to homepage (public resume view) after logout
header("location: index.php");
exit;
?>