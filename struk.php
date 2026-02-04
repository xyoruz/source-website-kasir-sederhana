<?php
include 'koneksi.php';

// Ambil ID Laporan dari URL
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

// Ambil data transaksi
$q_header = mysqli_query($conn, "SELECT * FROM laporan WHERE id = '$id' LIMIT 1");
$d = mysqli_fetch_array($q_header);

if(!$d){ die("Data struk tidak ditemukan."); }

// Format Tanggal & Jam
$tanggal = date('d/m/Y', strtotime($d['tanggal']));
$jam = date('H:i', strtotime($d['tanggal']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - XAILLA STORE</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace; /* Font khas struk */
            width: 58mm;  /* Lebar kertas thermal 58mm */
            margin: 0;
            padding: 2px 4px; /* Padding tipis agar tidak terpotong */
            font-size: 10px; /* Ukuran standar teks kecil */
            color: #000;
            line-height: 1.2;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .garis { border-top: 1px dashed #000; margin: 6px 0; }
        .flex { display: flex; justify-content: space-between; }
        
        /* HEADER NAMA TOKO BESAR */
        .nama-toko {
            font-size: 18px; /* Ukuran Besar */
            font-weight: 800;
            margin-bottom: 2px;
            display: block;
        }
        .alamat-toko {
            font-size: 9px;
            display: block;
        }

        /* ITEM BARANG */
        .item-name {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        
        /* DISCLAIMER / SYARAT */
        .disclaimer {
            font-size: 8px; /* Lebih kecil untuk syarat & ketentuan */
            margin-top: 8px;
            text-align: justify;
            border: 1px solid #000;
            padding: 4px;
        }
        .disclaimer ul { padding-left: 10px; margin: 2px 0; }

        @media print {
            @page { margin: 0; }
            body { margin: 0; padding: 2px 4px; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="center">
        <span class="nama-toko">XAILLA STORE</span>
        <span class="alamat-toko">Sparepart HP, Tools & Accessories</span>
        <span class="alamat-toko">ize.kesug.com</span>
    </div>
    
    <div class="garis"></div>
    
    <div class="flex">
        <span><?php echo $tanggal; ?></span>
        <span><?php echo $jam; ?></span>
    </div>
    <div class="flex">
        <span>No: #<?php echo $d['id']; ?></span>
        <span>Kasir: Admin</span>
    </div>
    <div class="flex">
        <span>Plg: <?php echo substr($d['pembeli'], 0, 18); ?></span>
    </div>
    
    <div class="garis"></div>
    
    <div style="margin-bottom: 5px;">
        <div class="item-name"><?php echo $d['nama_barang']; ?></div>
        <div class="flex">
            <span>1 x <?php echo number_format($d['harga_jual']); ?></span>
            <span><?php echo number_format($d['harga_jual']); ?></span>
        </div>
    </div>
    
    <div class="garis"></div>
    
    <div class="flex bold" style="font-size: 12px; margin-top: 4px;">
        <span>TOTAL</span>
        <span>Rp <?php echo number_format($d['harga_jual']); ?></span>
    </div>
    <div class="flex">
        <span>Tunai</span>
        <span>Rp <?php echo number_format($d['harga_jual']); ?></span>
    </div>
    <div class="flex">
        <span>Kembali</span>
        <span>Rp 0</span>
    </div>

    <div class="garis"></div>
    
    <div class="center bold" style="font-size: 9px; margin-top: 5px;">SYARAT GARANSI / RETUR</div>
    <div class="disclaimer">
        <b>Garansi Tes Fungsi (LCD/TS):</b>
        <ul>
            <li>Segel & Plastik pelindung UTUH.</li>
            <li>Fisik tidak cacat/pecah/fleksibel sobek.</li>
            <li>Belum terkena lem/cairan perekat.</li>
        </ul>
        <div class="center bold">SUDAH DIPASANG = GARANSI HANGUS</div>
    </div>

    <div class="center" style="margin-top: 10px; font-size: 9px;">
        -- Terima Kasih --<br>
        Barang yang dibeli tidak dapat ditukar uang.
    </div>

</body>
</html>
