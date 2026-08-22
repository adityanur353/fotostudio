<?php
/* =====================================================================
   register.php — Menerima data dari form Daftar (sec-daftar di index.html)
   dan menyimpannya ke tabel `users`.

   Dipanggil dari script.js pakai fetch() metode POST, body JSON:
   { "nama": "...", "email": "...", "password": "..." }

   Balasan (JSON):
   - Berhasil : { "success": true, "user": { "nama": "...", "email": "..." } }
   - Gagal    : { "success": false, "message": "..." }
===================================================================== */

header("Content-Type: application/json");
require_once "config.php";

// Ambil data JSON yang dikirim dari fetch()
$data = json_decode(file_get_contents("php://input"), true);

$nama = trim($data["nama"] ?? "");
$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";
$konfirmasi = $data["konfirmasi"] ?? "";

// --- Validasi dasar (sama seperti validasi yang tadinya di script.js) ---
if ($nama === "" || $email === "" || $password === "" || $konfirmasi === "") {
    echo json_encode(["success" => false, "message" => "Semua kolom wajib diisi."]);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(["success" => false, "message" => "Kata sandi minimal 6 karakter."]);
    exit;
}
if ($password !== $konfirmasi) {
    echo json_encode(["success" => false, "message" => "Konfirmasi kata sandi tidak cocok."]);
    exit;
}

$emailLower = strtolower($email);

// --- Cek email sudah terdaftar atau belum ---
$stmt = $koneksi->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
$stmt->bind_param("s", $emailLower);
$stmt->execute();
$hasil = $stmt->get_result();

if ($hasil->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email ini sudah terdaftar. Silakan masuk lewat halaman Login."]);
    $stmt->close();
    $koneksi->close();
    exit;
}
$stmt->close();

// --- Simpan user baru, password di-hash (bukan disimpan polos) ---
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $koneksi->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $nama, $email, $passwordHash);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "user" => [
            "id" => $stmt->insert_id,
            "nama" => $nama,
            "email" => $email
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Gagal menyimpan akun: " . $stmt->error]);
}

$stmt->close();
$koneksi->close();