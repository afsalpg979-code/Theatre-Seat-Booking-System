<?php
// Deprecated duplicate endpoint. Use the protected canonical OTP verifier.
header('Location: verify_otp.php', true, 301);
exit;
