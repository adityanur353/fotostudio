<?php
/* =====================================================================
   login.php — Menerima data dari form Masuk (sec-login di index.html)
   dan mencocokkannya dengan tabel `users`.

   Dipanggil dari script.js pakai fetch() metode POST, body JSON:
   { "email": "...", "password": "..." }

   Balasan (JSON):
   - Berhasil : { "success": true, "user": { "id":.., "nama": "...", "email": "..." } }
   - Gagal    : { "success": false, "message": "..." }
===================================================================== */

header("Content-Type: application/json");
require_once "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

if ($email === "" || $password === "") {
    echo json_encode(["success" => false, "message" => "Email dan kata sandi wajib diisi."]);
    exit;
}

$emailLower = strtolower($email);

$stmt = $koneksi->prepare("SELECT id, nama, email, password FROM users WHERE LOWER(email) = ?");
$stmt->bind_param("s", $emailLower);
$stmt->execute();
$hasil = $stmt->get_result();
$user = $hasil->fetch_assoc();
$stmt->close();

// Cocokkan password yang diketik dengan hash yang tersimpan
if (!$user || !password_verify($password, $user["password"])) {
    echo json_encode(["success" => false, "message" => "Email atau kata sandi salah. Belum punya akun? Daftar dulu di bawah."]);
    $koneksi->close();
    exit;
}

echo json_encode([
    "success" => true,
    "user" => [
        "id" => $user["id"],
        "nama" => $user["nama"],
        "email" => $user["email"]
    ]
]);

$koneksi->close();