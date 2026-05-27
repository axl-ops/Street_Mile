<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once 'koneksi.php';

/* =========================
   MYSQL ERROR HANDLING
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
   XSS SAFETY FUNCTION
========================= */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/* =========================
   AMBIL DATA STOK MINIMUM
========================= */
$data = query($conn, "
    SELECT 
        p.id,
        p.product_code,
        p.product_name,
        c.category_name,
        p.stock,
        p.min_stock,
        p.price,
        p.gambar,
        p.created_at
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.stock <= p.min_stock
    ORDER BY p.stock ASC
");

/* =========================
   INIT MPDF
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
    <title>Laporan Stok Minimum</title>
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
            color: #dc3545;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
            background-color: #f8f9fa;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        img {
            max-width: 70px;
            height: 70px;
            object-fit: cover;
        }

        .stok-minimum {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h1>Street Mile</h1>
    <h3>LAPORAN STOK MINIMUM</h3>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Gambar</th>
                <th>Kode Produk</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Stok Saat Ini</th>
                <th>Minimal Stok</th>
                <th>Status</th>
                <th>Tanggal Dibuat</th>
            </tr>
        </thead>

        <tbody>
';

/* =========================
   LOOP DATA (SAFE OUTPUT)
========================= */
$no = 1;

foreach ($data as $row) {

    $harga = "Rp " . number_format((float)$row['price'], 0, ',', '.');

    // gambar
    $gambarPath = 'produk_img/' . $row['gambar'];

    if (!empty($row['gambar']) && file_exists($gambarPath)) {
        $gambarHtml = '<img src="' . $gambarPath . '">';
    } else {
        $gambarHtml = '-';
    }

    $html .= '
        <tr>
            <td class="text-center">' . $no++ . '</td>
            <td class="text-center">' . $gambarHtml . '</td>
            <td class="text-center">' . e($row['product_code']) . '</td>
            <td>' . e($row['product_name']) . '</td>
            <td class="text-center">' . e($row['category_name']) . '</td>
            <td class="text-right">' . $harga . '</td>
            <td class="text-center">' . (int)$row['stock'] . '</td>
            <td class="text-center">' . (int)$row['min_stock'] . '</td>
            <td class="text-center"><span class="stok-minimum">Stok Minimum</span></td>
            <td class="text-center">' . date('d-m-Y H:i', strtotime($row['created_at'])) . '</td>
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
$mpdf->Output('laporan_stok_minimum.pdf', 'I');
