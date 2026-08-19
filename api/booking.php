<?php
/* =====================================================================
   booking.php — Menerima data dari halaman Pembayaran (tombol
   "Konfirmasi Pembayaran" di sec-pembayaran) dan menyimpannya SEKALIGUS
   ke tabel `bookings` dan `payments` (1 booking selalu punya 1 payment).

   Dipanggil dari script.js pakai fetch() metode POST, body JSON:
   {
     "user_id": 1,
     "package_id": "elevator",
     "nama": "...", "hp": "...", "email": "...",
     "tanggal": "2026-08-20", "jam": "16:00",
     "qty": 1, "total": 30000,
     "metode": "QRIS",
     "va": null, "ewallet_tujuan": null, "ewallet_pengirim": null
   }

   Balasan (JSON):
   - Berhasil : { "success": true, "trx": "SBX-XXXXXXXX", "booking_id": 5 }
   - Gagal    : { "success": false, "message": "..." }
===================================================================== */

header("Content-Type: application/json");
require_once "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$user_id    = intval($data["user_id"] ?? 0);
$package_id = trim($data["package_id"] ?? "");
$nama       = trim($data["nama"] ?? "");
$hp         = trim($data["hp"] ?? "");
$email      = trim($data["email"] ?? "");
$tanggal    = trim($data["tanggal"] ?? "");
$jam        = trim($data["jam"] ?? "");
$qty        = max(1, intval($data["qty"] ?? 1));
$total      = intval($data["total"] ?? 0);

$metode           = trim($data["metode"] ?? "");
$va               = $data["va"] ?? null;
$ewallet_tujuan   = $data["ewallet_tujuan"] ?? null;
$ewallet_pengirim = $data["ewallet_pengirim"] ?? null;

// --- Validasi dasar ---
if(!$user_id || !$package_id || !$nama || !$hp || !$email || !$tanggal || !$jam || !$metode){
    echo json_encode(["success" => false, "message" => "Data booking tidak lengkap."]);
    exit;
}

// --- Pastikan paket yang dipilih memang ada di tabel packages ---
$stmt = $koneksi->prepare("SELECT id FROM packages WHERE id = ?");
$stmt->bind_param("s", $package_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Paket tidak ditemukan."]);
    $stmt->close();
    $koneksi->close();
    exit;
}
$stmt->close();

// --- Simpan booking + payment sekaligus, dibungkus transaction supaya
//     dua-duanya tersimpan bareng (atau gagal dua-duanya, gak setengah2) ---
$koneksi->begin_transaction();

try {
    $stmt = $koneksi->prepare(
        "INSERT INTO bookings (user_id, package_id, nama_pemesan, hp, email, tanggal_sesi, jam_sesi, qty, total)
         VALUES (?,?,?,?,?,?,?,?,?)"
    );
    $stmt->bind_param("issssssii", $user_id, $package_id, $nama, $hp, $email, $tanggal, $jam, $qty, $total);
    $stmt->execute();
    $booking_id = $stmt->insert_id;
    $stmt->close();

    // Kode transaksi unik, format mirip yang dulu dibuat di script.js: SBX-XXXXXXXX
    $trx = "SBX-" . strtoupper(substr(uniqid(), -8));

    $stmt2 = $koneksi->prepare(
        "INSERT INTO payments (booking_id, trx_code, metode, va_number, ewallet_tujuan, ewallet_pengirim, status)
         VALUES (?,?,?,?,?,?, 'berhasil')"
    );
    $stmt2->bind_param("isssss", $booking_id, $trx, $metode, $va, $ewallet_tujuan, $ewallet_pengirim);
    $stmt2->execute();
    $stmt2->close();

    $koneksi->commit();

    echo json_encode(["success" => true, "trx" => $trx, "booking_id" => $booking_id]);
} catch (Exception $e) {
    $koneksi->rollback();
    echo json_encode(["success" => false, "message" => "Gagal menyimpan booking: " . $e->getMessage()]);
}

$koneksi->close();