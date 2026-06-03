<?php
$conn = new mysqli("localhost","root","","leave-management");

if($conn->connect_error){
  die("Connection failed");
}
?>