<?php
session_start();
session_unset();    // elimină toate variabilele din sesiune
session_destroy();  // distruge sesiunea
header("Location: login.html");
exit();
?>
