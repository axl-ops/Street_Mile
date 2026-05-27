<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once 'koneksi.php';

/* =========================
   ERROR HANDLING MYSQL
========================= */
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
   XSS SAFETY (FOR PDF OUTPUT)
========================= */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/* =========================
   AMBIL DATA BARANG MASUK (SAFE)
========================= */
$data = query($conn, "
    SELECT 
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
    JOIN products p ON sl.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON sl.created_by = u.id
    WHERE sl.change_type = 'ADD'
    ORDER BY sl.created_at DESC
");

/* =========================
   INIT PDF
========================= */
$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4-L'
]);

/* =========================
   HTML TEMPLATE
========================= */
$html = '
<html>
<head>
    <title>Laporan Barang Masuk</title>
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
            background-color: #198754;
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

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .qty-masuk {
            color: green;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h1>Street Mile</h1>
    <h3>LAPORAN BARANG MASUK</h3>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Kode Produk</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Qty Masuk</th>
                <th>Stok Sebelum</th>
                <th>Stok Sesudah</th>
                <th>Keterangan</th>
                <th>Diinput Oleh</th>
            </tr>
        </thead>
        <tbody>
';

/* =========================
   DATA LOOP (SAFE OUTPUT)
========================= */
$no = 1;

foreach ($data as $row) {

    $html .= '
        <tr>
            <td class="text-center">' . $no++ . '</td>
            <td class="text-center">' . date('d-m-Y H:i', strtotime($row['created_at'])) . '</td>
            <td>' . e($row['product_code']) . '</td>
            <td>' . e($row['product_name']) . '</td>
            <td>' . e($row['category_name']) . '</td>
            <td class="text-center qty-masuk">+' . (int)$row['qty'] . '</td>
            <td class="text-center">' . (int)$row['stock_before'] . '</td>
            <td class="text-center">' . (int)$row['stock_after'] . '</td>
            <td>' . e($row['note']) . '</td>
            <td class="text-center">' . e($row['created_by']) . '</td>
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
$mpdf->Output('laporan_barang_masuk.pdf', 'I');
