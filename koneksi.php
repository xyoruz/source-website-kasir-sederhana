<?php
$host = "sql101.infinityfree.com"; 
$user = "if0_41049640";    
$pass = "Sihab020";    
// Nama DB harus persis seperti di gambar List of MySQL Databases Anda
$db   = "if0_41049640_if0_41049640_xaillastore"; 

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
