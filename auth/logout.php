<?php
session_start();
session_destroy();
header('Location: /inventory/auth/login.php');
exit;