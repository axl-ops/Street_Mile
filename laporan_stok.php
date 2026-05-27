<?php

declare(strict_types=1);

// Composer autoload (mPDF)
require_once __DIR__ . '/vendor/autoload.php';

// Koneksi database
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
   FETCH DATA
========================= */
$data = query(
    $conn,
    "SELECT 
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
     LEFT JOIN categories c ON p.category_id = c.id
     ORDER BY p.product_name ASC"
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
    <title>Laporan Stok Barang</title>
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
            background-color: #4e73df;
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

        .text-right {
            text-align: right;
        }

        img {
            width: 70px;
            height: 70px;
            object-fit: cover;
        }

        .stok-aman {
            color: green;
            font-weight: bold;
        }

        .stok-minim {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>

<h1>Street Mile</h1>
<hr>
<h3>LAPORAN STOK BARANG</h3>

<table>
<thead>
<tr>
    <th>No</th>
    <th>Gambar</th>
    <th>Kode Produk</th>
    <th>Nama Produk</th>
    <th>Kategori</th>
    <th>Harga</th>
    <th>Stok</th>
    <th>Min. Stok</th>
    <th>Status</th>
    <th>Tanggal Dibuat</th>
</tr>
</thead>
<tbody>
';

$no = 1;

/* =========================
   LOOP DATA (SAFE OUTPUT)
========================= */
foreach ($data as $row) {

    $harga = "Rp " . number_format((float)$row['price'], 0, ',', '.');

    // status stok (FIX LOGIC)
    $status = ($row['stock'] <= $row['min_stock'])
        ? '<span class="stok-minim">Stok Minim</span>'
        : '<span class="stok-aman">Aman</span>';

    // gambar
    $gambarPath = __DIR__ . '/produk_img/' . $row['gambar'];
    $gambarHtml = '-';

    if (!empty($row['gambar']) && file_exists($gambarPath)) {
        $gambarHtml = '<img src="produk_img/' . htmlspecialchars($row['gambar'], ENT_QUOTES, 'UTF-8') . '">';
    }

    // SAFE OUTPUT (anti broken PDF + XSS-safe string handling)
    $html .= '
    <tr>
        <td class="text-center">' . $no++ . '</td>
        <td class="text-center">' . $gambarHtml . '</td>
        <td>' . htmlspecialchars($row['product_code'], ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($row['category_name'] ?? "-", ENT_QUOTES, 'UTF-8') . '</td>
        <td class="text-right">' . $harga . '</td>
        <td class="text-center">' . (int)$row['stock'] . '</td>
        <td class="text-center">' . (int)$row['min_stock'] . '</td>
        <td class="text-center">' . $status . '</td>
        <td class="text-center">' . (!empty($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : '-') . '</td>
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
$mpdf->Output('Laporan_Stok_Barang.pdf', 'I');
