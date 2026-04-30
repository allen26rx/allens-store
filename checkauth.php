<?php

$user_id = $_SESSION["id"];

if (!$user_id) {
header("location:login.php");
}



?>