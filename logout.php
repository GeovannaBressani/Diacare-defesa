<?php
session_start();
session_destroy();
header("Location: index.html"); // ou a página de login
exit();
// sair da conta
?>
