<?php

session_start();

include("checkauth.php");


   $_SESSION["id"]= 0;
   $_SESSION["name"] = "";
   $_SESSION["email"]= "";





session_destroy();

header("loaction:login.php");
?>