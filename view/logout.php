<?php
require_once __DIR__ . '/../model/config/session.php';
require_once __DIR__ . '/../controller/AuthController.php';

$auth = new AuthController();
$auth->logout();

header("Location: index.php?success=Logged out successfully");
exit();
?>