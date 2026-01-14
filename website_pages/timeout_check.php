<?php
// Only run timeout logic if logged in
if (isset($_SESSION['user_id']))
{
    $timeout = 300; // 5 minutes

    // If LAST_ACTIVITY is not set, initialize it
    if (!isset($_SESSION['LAST_ACTIVITY']))
    {
        $_SESSION['LAST_ACTIVITY'] = time();
    }

    // Check inactivity
    if (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)
    {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }

    // Update activity timestamp
    $_SESSION['LAST_ACTIVITY'] = time();
}
?>
