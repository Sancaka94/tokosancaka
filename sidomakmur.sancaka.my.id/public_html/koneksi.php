<?php
$host = "localhost";
$user = "tokq3391_sidomakmur";
$pass = "Salafyyin***94";
$db   = "tokq3391_sidomakmur";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>