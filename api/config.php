<?php
/* =====================================================================
   config.php — Koneksi ke database MySQL "shotbox"
   Semua file PHP lain (register.php, login.php, dst) tinggal
   require_once file ini di baris paling atas untuk dapat koneksinya
   lewat variabel $koneksi.

   CATATAN: setting di bawah ini untuk hosting InfinityFree
   (akun if0_42693606). Kalau kamu bikin ulang database dengan nama
   lain, tinggal ganti $nama_db sesuai nama database barunya
   (formatnya selalu if0_42693606_NAMA_DATABASE).
===================================================================== */

$host = "sql304.infinityfree.com";
$user = "if0_42693606";
$pass = "u3CLi14fPK";   // <-- klik ikon mata di dashboard, salin ke sini
$nama_db = "if0_42693606_studio1";

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