<?php
/* =====================================================================
   config.php — Koneksi ke database MySQL "shotbox"
   Semua file PHP lain (register.php, login.php, dst) tinggal
   require_once file ini di baris paling atas untuk dapat koneksinya
   lewat variabel $koneksi.

   CATATAN: setting di bawah ini standar default XAMPP:
   - host   : localhost
   - user   : root
   - pass   : (kosong)
   Kalau MySQL XAMPP kamu sudah diberi password, ganti $pass di bawah.
===================================================================== */

$host = "localhost";
$user = "root";
$pass = "";
$nama_db = "shotbox";

$koneksi = new mysqli($host, $user, $pass, $nama_db);

if ($koneksi->connect_error) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "message" => "Koneksi database gagal: " . $koneksi->connect_error
    ]);
    exit;
}

$koneksi->set_charset("utf8mb4");