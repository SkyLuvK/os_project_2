<?php
session_start();

if (isset($_POST['otp']) && isset($_POST['filename'])) {
    if ($_POST['otp'] == $_SESSION['otp'] && time() < $_SESSION['otp_expiry']) {
        echo "verified";
    } else {
        echo "Invalid OTP. Please try again.";
    }
}
?>
