<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once 'koneksi.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =========================
   SAFE QUERY FUNCTION
========================= */
function query(mysqli $conn, string $sql): array
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        error_log("Query Error: " . mysqli_error($conn));
        return [];
    }

    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

/* =========================
   FETCH DATA BARANG KELUAR
========================= */
$data = query(
    $conn,
    "SELECT 
        sl.id,
        p.product_code,
        p.product_name,
        c.category_name,
        sl.qty,
        sl.stock_before,
        sl.stock_after,
        sl.note,
        sl.created_at,
        u.name AS created_by
     FROM stock_logs sl
     INNER JOIN products p ON sl.product_id = p.id
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN users u ON sl.created_by = u.id
     WHERE sl.change_type = 'REDUCE'
     ORDER BY sl.created_at DESC"
);

/* =========================
   INIT MPDF
========================= */
$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4-L'
]);

/* =========================
   HTML START
========================= */
$html = '
<html>
<head>
    <title>Laporan Barang Keluar</title>
    <style>
        body { font-family: sans-serif; }

        h1 {
            text-align: center;
            color: #262626;
            margin-bottom: 5px;
        }

        h3 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead th {
            background-color: #dc3545;
            color: white;
            padding: 10px;
            font-size: 12px;
        }

        tbody td {
            padding: 8px;
            font-size: 11px;
            border: 1px solid #ccc;
        }

        tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .text-center {
            text-align: center;
        }

        .qty-keluar {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>

<h1>Laporan Barang Keluar</h1>
<h3>Inventaris Gudang</h3>

<table>
<thead>
<tr>
    <th>No</th>
    <th>Tanggal</th>
    <th>Kode Produk</th>
    <th>Nama Produk</th>
    <th>Kategori</th>
    <th>Qty Keluar</th>
    <th>Stok Sebelum</th>
    <th>Stok Sesudah</th>
    <th>Keterangan</th>
    <th>Diinput Oleh</th>
</tr>
</thead>
<tbody>
';

$no = 1;

/* =========================
   LOOP DATA (SAFE OUTPUT)
========================= */
foreach ($data as $row) {

    $tanggal = !empty($row['created_at'])
        ? date('d-m-Y H:i', strtotime($row['created_at']))
        : '-';

    $html .= '
    <tr>
        <td class="text-center">' . $no++ . '</td>
        <td class="text-center">' . $tanggal . '</td>
        <td class="text-center">' . htmlspecialchars($row['product_code'] ?? "-", ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($row['product_name'] ?? "-", ENT_QUOTES, 'UTF-8') . '</td>
        <td class="text-center">' . htmlspecialchars($row['category_name'] ?? "-", ENT_QUOTES, 'UTF-8') . '</td>
        <td class="text-center qty-keluar">' . (int)$row['qty'] . '</td>
        <td class="text-center">' . (int)$row['stock_before'] . '</td>
        <td class="text-center">' . (int)$row['stock_after'] . '</td>
        <td>' . htmlspecialchars($row['note'] ?? "-", ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($row['created_by'] ?? "-", ENT_QUOTES, 'UTF-8') . '</td>
    </tr>
    ';
}

$html .= '
</tbody>
</table>

</body>
</html>
';

/* =========================
   OUTPUT PDF
========================= */
$mpdf->WriteHTML($html);
$mpdf->Output('laporan_barang_keluar.pdf', 'I');
