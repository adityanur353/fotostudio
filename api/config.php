<?php
/* =====================================================================
   config.php — Koneksi ke database MySQL "shotbox"
   Semua file PHP lain (register.php, login.php, dst) tinggal
   require_once file ini di baris paling atas untuk dapat koneksinya
   lewat variabel $koneksi.

   CATATAN: setting di bawah ini untuk hosting InfinityFree
   (akun if0_42709569). Kalau kamu bikin ulang database dengan nama
   lain, tinggal ganti $nama_db sesuai nama database barunya
   (formatnya selalu if0_42709569_NAMA_DATABASE).
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