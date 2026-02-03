<?php
include 'koneksi.php';

// Mengambil ID dari kiriman klik tombol di index.php
$id = mysqli_real_escape_string($conn, $_GET['id']);

// Mencari data transaksi yang memiliki ID Harian tersebut
$q = mysqli_query($conn, "SELECT nama_barang, pembeli, harga_jual FROM laporan WHERE id_harian = '$id'");

echo "<table style='width:100%; border-collapse:collapse; font-size:0.85rem;'>
        <tr style='background:#f1f5f9;'>
            <th style='padding:8px; text-align:left;'>Barang</th>
            <th style='padding:8px; text-align:left;'>Pembeli</th>
            <th style='padding:8px; text-align:right;'>Harga</th>
        </tr>";

$total = 0;
while($r = mysqli_fetch_array($q)){
    $total += $r['harga_jual'];
    echo "<tr>
            <td style='padding:8px; border-bottom:1px solid #eee;'>{$r['nama_barang']}</td>
            <td style='padding:8px; border-bottom:1px solid #eee;'>{$r['pembeli']}</td>
            <td style='padding:8px; border-bottom:1px solid #eee; text-align:right;'>".number_format($r['harga_jual'])."</td>
          </tr>";
}
echo "</table>";

// Menampilkan total pendapatan khusus ID tersebut di bawah tabel pop-up
echo "<div style='text-align:right; padding:10px; margin-top:5px; border-top:2px solid #3b82f6;'>
        <small>Total Pendapatan ID:</small>
        <h3 style='margin:0; color:#10b981;'>Rp ".number_format($total)."</h3>
      </div>";
?>
