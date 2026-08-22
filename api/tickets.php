<?php
/* =====================================================================
   tickets.php — Mengambil riwayat booking + pembayaran milik 1 user,
   dipakai oleh halaman "Tiket Saya" (sec-tiket di index.html).

   Dipanggil dari script.js pakai fetch() metode GET:
   api/tickets.php?user_id=1

   Balasan (JSON):
   { "success": true, "tickets": [ { trx, itemName, nama, jadwal,
     detail, metode, total, refInfo }, ... ] }
===================================================================== */

header("Content-Type: application/json");
require_once "config.php";

$user_id = intval($_GET["user_id"] ?? 0);

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "user_id tidak valid.", "tickets" => []]);
    exit;
}

$sql = "SELECT p.trx_code, pk.name AS item_name, b.nama_pemesan, b.tanggal_sesi, b.jam_sesi,
               b.qty, b.total, p.metode, p.va_number, p.ewallet_tujuan, p.ewallet_pengirim, p.paid_at
        FROM bookings b
        JOIN payments p  ON p.booking_id = b.id
        JOIN packages pk ON pk.id = b.package_id
        WHERE b.user_id = ?
        ORDER BY p.paid_at DESC";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$hasil = $stmt->get_result();

$tickets = [];
while ($row = $hasil->fetch_assoc()) {
    // Susun baris "Referensi" sama seperti yang dulu dibuat paymentRefLine() di script.js
    $refInfo = "";
    if ($row["metode"] === "Transfer Bank BCA" && $row["va_number"]) {
        $refInfo = "VA BCA: " . $row["va_number"];
    } elseif ($row["metode"] === "QRIS") {
        $refInfo = "Dibayar via scan QRIS";
    } elseif ($row["metode"] === "E-Wallet (GoPay/OVO/Dana)" && $row["ewallet_pengirim"]) {
        $refInfo = "Dari: " . $row["ewallet_pengirim"] . " → Ke: " . $row["ewallet_tujuan"];
    }

    $tickets[] = [
        "trx"      => $row["trx_code"],
        "itemName" => $row["item_name"],
        "nama"     => $row["nama_pemesan"],
        "jadwal"   => $row["tanggal_sesi"] . " • " . substr($row["jam_sesi"], 0, 5),
        "detail"   => $row["qty"] . " sesi",
        "metode"   => $row["metode"],
        "total"    => (int) $row["total"],
        "refInfo"  => $refInfo
    ];
}

echo json_encode(["success" => true, "tickets" => $tickets]);

$stmt->close();
$koneksi->close();