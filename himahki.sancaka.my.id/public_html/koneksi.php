<?php
$host = "localhost";
$user = "tokq3391_hima";
$pass = "Salafyyin***94";
$db   = "tokq3391_hima";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>