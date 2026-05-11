<?php
// $mysqli = new mysqli("localhost","","","");
$mysqli = new mysqli("localhost","root","","bme_comp");
// Check connection
if ($mysqli -> connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
  exit();
}
?>
