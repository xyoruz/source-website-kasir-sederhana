<?php
session_start();

// --- 1. FITUR KEAMANAN: AUTO LOGOUT 30 DETIK (Server Side) ---
// Jika waktu aktivitas terakhir ada, dan selisihnya > 30 detik, maka logout.
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 30)) {
    session_unset();     
    session_destroy();   
    header("Location: login.php");
    exit;
}
// Update waktu aktivitas terakhir setiap kali halaman dimuat/akses AJAX
$_SESSION['LAST_ACTIVITY'] = time(); 
// -------------------------------------------------------------

if(!isset($_SESSION['admin'])){ header("Location: login.php"); exit; }
include 'koneksi.php';

// Variabel ID Harian Otomatis (Format: ID-YYYYMMDD)
$id_harian_sekarang = "ID-" . date('Ymd');

// --- AJAX HANDLER ---

// 1. Cek Member
if(isset($_POST['cek_member'])){
    $nama = mysqli_real_escape_string($conn, $_POST['nama_cari']);
    $q = mysqli_query($conn, "SELECT * FROM member WHERE nama_member = '$nama'");
    if(mysqli_num_rows($q) > 0){ echo "ada"; } else { echo "tidak_ada"; }
    exit;
}

// 2. Tambah Member Baru
if(isset($_POST['tambah_member_baru'])){
    $nama = mysqli_real_escape_string($conn, $_POST['nama_baru']);
    mysqli_query($conn, "INSERT INTO member (nama_member) VALUES ('$nama')");
    echo "sukses"; exit;
}

// 3. Detail Laporan Kasir
if(isset($_POST['get_detail_laporan'])){
    $id_h = mysqli_real_escape_string($conn, $_POST['id_harian']);
    $q_hitung = mysqli_query($conn, "SELECT SUM(harga_jual) as total_omzet, SUM(untung) as total_profit FROM laporan WHERE id_harian = '$id_h'");
    $hitung = mysqli_fetch_array($q_hitung);
    echo "<div style='background:rgba(22, 101, 52, 0.2); color:#22c55e; padding:15px; border-radius:12px; margin-bottom:15px; text-align:center; border:1px solid rgba(34, 197, 94, 0.3);'>
            <h3 style='margin:0;'>Total Pendapatan: Rp ".number_format($hitung['total_omzet'] ?? 0)."</h3>
            <small>Total Keuntungan Bersih: Rp ".number_format($hitung['total_profit'] ?? 0)."</small>
          </div>";
    $q = mysqli_query($conn, "SELECT * FROM laporan WHERE id_harian = '$id_h'");
    echo "<div style='overflow-x:auto;'><table class='glass-table'>
            <thead><tr><th>Barang</th><th>Tipe</th><th>Harga</th><th>Pembeli</th></tr></thead><tbody>";
    while($d = mysqli_fetch_array($q)){
        echo "<tr><td>{$d['nama_barang']}</td><td>{$d['tipe_pembeli']}</td><td>".number_format($d['harga_jual'])."</td><td>{$d['pembeli']}</td></tr>";
    }
    echo "</tbody></table></div>";
    exit;
}

// 4. Detail Data Faktur
if(isset($_POST['get_detail_faktur'])){
    $no_f = mysqli_real_escape_string($conn, $_POST['no_faktur']);
    $q = mysqli_query($conn, "SELECT * FROM history_faktur WHERE no_faktur = '$no_f'");
    echo "<div style='overflow-x:auto;'><table class='glass-table'>
            <thead><tr><th>Barang</th><th>Stok Masuk</th><th>Modal</th><th>Member</th><th>Ecer</th></tr></thead><tbody>";
    while($d = mysqli_fetch_array($q)){
        echo "<tr><td>{$d['nama_barang']}</td><td>{$d['stok_masuk']}</td><td>".number_format($d['modal'])."</td><td>".number_format($d['member'])."</td><td>".number_format($d['ecer'])."</td></tr>";
    }
    echo "</tbody></table></div>";
    exit;
}

// 5. Simpan Faktur & Update Stok
if(isset($_POST['simpan_faktur'])){
    $suplayer = mysqli_real_escape_string($conn, $_POST['suplayer']);
    $no_faktur = mysqli_real_escape_string($conn, $_POST['no_faktur']);
    $tgl_nota = mysqli_real_escape_string($conn, $_POST['tgl_nota']);
    
    $nama_barang = $_POST['f_nama'];
    $stok_baru = $_POST['f_stok'];
    $modal = $_POST['f_modal'];
    $member = $_POST['f_member'];
    $ecer = $_POST['f_ecer'];

    for($i=0; $i < count($nama_barang); $i++){
        $nb = mysqli_real_escape_string($conn, $nama_barang[$i]);
        $sb = (int)$stok_baru[$i];
        $md = (int)$modal[$i];
        $mb = (int)$member[$i];
        $ec = (int)$ecer[$i];

        if(!empty($nb)){
            mysqli_query($conn, "INSERT INTO history_faktur (no_faktur, suplayer, tgl_nota, nama_barang, stok_masuk, modal, member, ecer, tanggal_input) 
                                VALUES ('$no_faktur', '$suplayer', '$tgl_nota', '$nb', '$sb', '$md', '$mb', '$ec', NOW())");
            
            $cek = mysqli_query($conn, "SELECT id, stok FROM produk WHERE nama_barang = '$nb'");
            if(mysqli_num_rows($cek) > 0){
                $r = mysqli_fetch_array($cek);
                $stok_akhir = $r['stok'] + $sb;
                mysqli_query($conn, "UPDATE produk SET stok = '$stok_akhir', modal = '$md', member = '$mb', ecer = '$ec' WHERE id = '{$r['id']}'");
            } else {
                mysqli_query($conn, "INSERT INTO produk (nama_barang, modal, member, ecer, stok) VALUES ('$nb', '$md', '$mb', '$ec', '$sb')");
            }
        }
    }
    header("Location: index.php"); exit;
}

// 6. Update Produk Manual
if(isset($_POST['update_produk_full'])){
    $id = mysqli_real_escape_string($conn, $_POST['id_p']);
    $n = mysqli_real_escape_string($conn, $_POST['nama_b']);
    $m = (int)$_POST['modal_p'];
    $mem = (int)$_POST['mem_p'];
    $e = (int)$_POST['ecer_p'];
    $s = (int)$_POST['stok_p'];
    mysqli_query($conn, "UPDATE produk SET nama_barang='$n', modal='$m', member='$mem', ecer='$e', stok='$s' WHERE id='$id'");
    header("Location: index.php"); exit;
}

// 7. Hapus Produk
if(isset($_POST['hapus_produk_aksi'])){
    $id = mysqli_real_escape_string($conn, $_POST['id_p_hapus']);
    mysqli_query($conn, "DELETE FROM produk WHERE id='$id'");
    header("Location: index.php"); exit;
}

// 8. Transaksi Kasir Final
if(isset($_POST['final_jual'])){
    $id_b = mysqli_real_escape_string($conn, $_POST['id_b']);
    $tipe = $_POST['tipe_p'];
    $nama_inp = mysqli_real_escape_string($conn, $_POST['nama_pembeli']);
    $pembeli = ($tipe == 'ecer') ? 'Ecer' : $nama_inp;
    $cetak = $_POST['pilihan_cetak'];
    $res = mysqli_query($conn, "SELECT * FROM produk WHERE id='$id_b'");
    if($b = mysqli_fetch_array($res)){
        $harga = ($tipe == 'member') ? $b['member'] : $b['ecer'];
        $stok_sisa = $b['stok'] - 1;
        $untung = $harga - $b['modal'];
        mysqli_query($conn, "INSERT INTO laporan (id_harian, nama_barang, tipe_pembeli, harga_jual, untung, modal_asli, stok_sisa, pembeli, tanggal) VALUES ('$id_harian_sekarang', '{$b['nama_barang']}', '$tipe', '$harga', '$untung', '{$b['modal']}', '$stok_sisa', '$pembeli', NOW())");
        mysqli_query($conn, "UPDATE produk SET stok = $stok_sisa WHERE id='$id_b'");
        if($cetak == 'ya'){ echo "<script>alert('Berhasil!'); window.print(); window.location='index.php';</script>"; } 
        else { header("Location: index.php"); }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XAILLA STORE - Modern Glass</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Light Mode Variables */
            --bg-gradient: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%);
            --glass-bg: rgba(255, 255, 255, 0.45);
            --glass-border: rgba(255, 255, 255, 0.6);
            --text-color: #1e293b;
            --text-muted: #64748b;
            --input-bg: rgba(255, 255, 255, 0.5);
            --primary: #3b82f6;
            --sidebar-glass: rgba(255, 255, 255, 0.65);
        }

        [data-theme="dark"] {
            /* Dark Mode Variables */
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #312e81 100%);
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-color: #f1f5f9;
            --text-muted: #94a3b8;
            --input-bg: rgba(0, 0, 0, 0.3);
            --primary: #6366f1;
            --sidebar-glass: rgba(15, 23, 42, 0.75);
        }

        * { box-sizing: border-box; }
        
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            background-attachment: fixed; /* Agar background tetap saat scroll */
            color: var(--text-color);
            transition: 0.3s ease;
            min-height: 100vh;
        }

        /* --- GLASSMORPHISM COMPONENTS --- */

        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.2s;
        }

        .glass-sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            left: -280px;
            top: 0;
            background: var(--sidebar-glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-right: 1px solid var(--glass-border);
            z-index: 10000;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            padding-top: 20px;
        }
        .glass-sidebar.active { left: 0; }

        .glass-topbar {
            position: fixed;
            top: 15px; left: 15px; right: 15px;
            height: 70px;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            padding: 0 25px;
            z-index: 9999;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        /* --- UI ELEMENTS --- */

        .main { margin-top: 100px; padding: 0 15px 20px 15px; max-width: 1200px; margin-left: auto; margin-right: auto; }
        
        h1, h3 { margin-top: 0; font-weight: 600; letter-spacing: -0.5px; }
        
        /* Inputs */
        input, select, button {
            width: 100%;
            padding: 12px 15px;
            margin: 8px 0;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: var(--input-bg);
            color: var(--text-color);
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: 0.3s;
        }
        input:focus, select:focus {
            background: rgba(255,255,255,0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        input[readonly] {
            opacity: 0.7;
            cursor: not-allowed;
            border-style: dashed;
        }

        /* Buttons */
        button, .btn-yes, .btn-no, .btn-danger {
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-yes { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .btn-no { background: rgba(100, 116, 139, 0.5); color: white; border: 1px solid rgba(255,255,255,0.2); }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }

        /* Sidebar Menu */
        .menu-item {
            padding: 15px 25px;
            cursor: pointer;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
            transition: 0.2s;
            border-left: 3px solid transparent;
            margin: 2px 10px;
            border-radius: 8px;
        }
        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: var(--primary);
            padding-left: 30px;
        }
        .theme-toggle { margin: 20px; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 10px; cursor: pointer; text-align: center; font-size: 0.9rem; font-weight: bold; color: #fbbf24; }

        /* Tables */
        .glass-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; margin-top: 10px; }
        .glass-table th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--glass-border); }
        .glass-table td { padding: 15px; background: rgba(255,255,255,0.05); first-child: border-radius: 10px 0 0 10px; last-child: border-radius: 0 10px 10px 0; border: none; }
        .glass-table tr { transition: 0.2s; }
        .glass-table tr:hover td { background: rgba(255,255,255,0.15); transform: scale(1.01); }

        /* ID Box Special */
        .box-id {
            background: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%);
            color: white;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            cursor: pointer;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.5);
            position: relative; overflow: hidden;
        }
        .box-id::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
            transform: rotate(45deg); pointer-events: none;
        }

        /* Modals */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); z-index: 20000; justify-content: center; align-items: center; padding: 20px; animation: fadeIn 0.3s; }
        .modal-content {
            background: var(--glass-bg); /* Sesuai tema */
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            color: var(--text-color);
            padding: 30px;
            border-radius: 20px;
            width: 100%; max-width: 500px;
            max-height: 85vh; overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            animation: slideUp 0.3s;
        }

        /* Animations & Helpers */
        .page { display: none; animation: fadeIn 0.4s ease-out; }
        .show { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        #copyMsg { display: none; background: rgba(16, 185, 129, 0.9); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .brand { font-size: 1.5rem; font-weight: 700; background: linear-gradient(to right, #6366f1, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body>

    <div class="glass-sidebar" id="sb">
        <div style="padding: 0 25px 20px 25px;">
            <div class="brand">XAILLA</div>
            <div style="font-size: 0.8rem; opacity: 0.7; letter-spacing: 2px;">STORE DASHBOARD</div>
        </div>
        
        <div class="menu-item" onclick="pg('k')"><span>🛒</span> Kasir Transaksi</div>
        <div class="menu-item" onclick="pg('f')"><span>📦</span> Input Faktur Baru</div>
        <div class="menu-item" onclick="pg('h')"><span>📜</span> History & Arsip</div>
        <div class="menu-item" onclick="pg('m')"><span>✏️</span> Kelola Produk</div>
        
        <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <div class="theme-toggle" onclick="toggleTheme()"><span id="theme-icon">🌙</span> <span id="theme-text">Mode Gelap</span></div>
            <div class="menu-item" onclick="konfirmasiKeluar()" style="color:#f87171;"><span>🚪</span> Keluar Aplikasi</div>
        </div>
    </div>

    <div class="glass-topbar">
        <div style="display:flex; align-items:center;">
            <span onclick="tog()" style="font-size:24px; cursor:pointer; margin-right: 20px; color: var(--primary);">☰</span>
            <b style="font-size: 1.1rem; opacity:0.9;">Store Management</b>
        </div>
    </div>

    <div class="main">
        
        <div id="k" class="page show">
            <div class="box-id" onclick="copyID('<?= $id_harian_sekarang ?>')" title="Klik untuk menyalin ID">
                <div style="width:100%;">
                    <b style="font-size: 1.4rem; letter-spacing: 1px;"><?= $id_harian_sekarang ?></b>
                </div>
                <div style="position: absolute; right: 20px; top: 20px;">
                    <span id="copyMsg">ID Berhasil Disalin!</span>
                </div>
            </div>

            <div class="glass-panel">
                <input type="text" onkeyup="filterT(this,'tK', 1)" placeholder="🔍 Cari nama barang untuk transaksi..." style="padding: 15px; font-size: 1rem;">
                <div style="overflow-x:auto;">
                    <table id="tK" class="glass-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Barang</th>
                                <th>Stok</th>
                                <th>Modal</th>
                                <th>Member</th>
                                <th>Ecer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; $p=mysqli_query($conn,"SELECT * FROM produk ORDER BY nama_barang ASC");
                            while($r=mysqli_fetch_array($p)){ ?>
                                <tr onclick="tanyaBeli('<?= $r['id'] ?>', '<?= $r['nama_barang'] ?>')" style="cursor:pointer;">
                                    <td><?= $no++ ?></td>
                                    <td><b><?= $r['nama_barang'] ?></b></td>
                                    <td><span style="background:rgba(99, 102, 241, 0.2); color:var(--primary); padding:2px 8px; border-radius:6px;"><?= $r['stok'] ?></span></td>
                                    <td><?= number_format($r['modal']) ?></td>
                                    <td><?= number_format($r['member']) ?></td>
                                    <td><?= number_format($r['ecer']) ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="f" class="page">
            <div class="glass-panel">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3>📦 Input Faktur Baru</h3>
                </div>
                <form id="formFaktur" method="POST">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:20px;">
                        <div><label style="font-size:0.85rem; opacity:0.7;">Toko Suplayer</label><input type="text" name="suplayer" placeholder="Nama toko..." required></div>
                        <div><label style="font-size:0.85rem; opacity:0.7;">No. Faktur</label><input type="text" name="no_faktur" placeholder="Kode faktur..." required></div>
                        <div>
                            <label style="font-size:0.85rem; opacity:0.7;">Waktu Input (Otomatis)</label>
                            <input type="datetime-local" name="tgl_nota" id="liveClockInput" readonly required style="background: rgba(0,0,0,0.05);">
                        </div>
                    </div>
                    <div style="overflow-x:auto;">
                        <table id="tabelInputFaktur" class="glass-table">
                            <thead><tr><th>No</th><th>Nama Barang</th><th>Stok+</th><th>Modal</th><th>Member</th><th>Ecer</th></tr></thead>
                            <tbody>
                                <?php for($i=1; $i<=5; $i++){ ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><input type="text" name="f_nama[]" placeholder="Item..."></td>
                                    <td><input type="number" name="f_stok[]" placeholder="0"></td>
                                    <td><input type="number" name="f_modal[]" placeholder="Rp"></td>
                                    <td><input type="number" name="f_member[]" placeholder="Rp"></td>
                                    <td><input type="number" name="f_ecer[]" placeholder="Rp"></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:20px;">
                        <button type="button" onclick="tambahBarisFaktur()" style="background:var(--glass-bg); color:var(--text-color); border:1px solid var(--glass-border);">+ Tambah Baris</button>
                        <button type="button" onclick="konfirmasiSimpanFaktur()" class="btn-yes" style="flex-grow:1;">SIMPAN DATA FAKTUR</button>
                    </div>
                    <input type="submit" name="simpan_faktur" id="submit_faktur" style="display:none;">
                </form>
            </div>
        </div>

        <div id="h" class="page">
             <div class="glass-panel" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border:none;">
                <p style="margin:0; opacity: 0.8; font-size:0.9rem;">Total Omzet Hari Ini</p>
                <?php $t=mysqli_fetch_array(mysqli_query($conn,"SELECT SUM(harga_jual) as tot FROM laporan WHERE id_harian='$id_harian_sekarang'")); ?>
                <h1 style="margin:5px 0 0 0; font-size: 2.5rem;">Rp <?= number_format($t['tot'] ?? 0) ?></h1>
            </div>
            
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
                <div class="glass-panel">
                    <h3>📜 Arsip Laporan Harian</h3>
                    <input type="text" onkeyup="filterT(this,'tID', 1)" placeholder="Cari ID Laporan..." style="margin-bottom:15px;">
                    <div style="max-height:400px; overflow-y:auto;">
                        <table id="tID" class="glass-table">
                            <thead><tr><th>No</th><th>ID Laporan</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php $ni=1; $gi=mysqli_query($conn,"SELECT id_harian FROM laporan GROUP BY id_harian ORDER BY tanggal DESC");
                                while($ri=mysqli_fetch_array($gi)){ ?>
                                    <tr>
                                        <td><?= $ni++ ?></td>
                                        <td><b><?= $ri['id_harian'] ?></b></td>
                                        <td><button onclick="lihatDetail('<?= $ri['id_harian'] ?>')" style="background:rgba(16, 185, 129, 0.2); color:#10b981; padding:5px 10px; border-radius:8px;">Buka</button></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="glass-panel">
                    <h3>📦 History Faktur Masuk</h3>
                    <input type="text" onkeyup="filterT(this,'tFakt', 1)" placeholder="Cari No Faktur..." style="margin-bottom:15px;">
                    <div style="max-height:400px; overflow-y:auto;">
                        <table id="tFakt" class="glass-table">
                            <thead><tr><th>No</th><th>Faktur</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php $nf=1; $gf=mysqli_query($conn,"SELECT no_faktur, suplayer FROM history_faktur GROUP BY no_faktur ORDER BY tanggal_input DESC");
                                while($rf=mysqli_fetch_array($gf)){ ?>
                                    <tr>
                                        <td><?= $nf++ ?></td>
                                        <td>
                                            <b><?= $rf['no_faktur'] ?></b><br>
                                            <span style="font-size:0.8rem; opacity:0.6;"><?= $rf['suplayer'] ?></span>
                                        </td>
                                        <td><button onclick="lihatDetailFaktur('<?= $rf['no_faktur'] ?>')" style="background:rgba(245, 158, 11, 0.2); color:#f59e0b; padding:5px 10px; border-radius:8px;">Buka</button></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="m" class="page">
            <div class="glass-panel">
                <h3>✏️ Kelola Database Produk</h3>
                <input type="text" onkeyup="filterT(this,'tM', 1)" placeholder="Cari nama barang untuk diedit..." style="margin-bottom:15px;">
                <div style="overflow-x:auto;">
                    <table id="tM" class="glass-table">
                        <thead><tr><th>Produk</th><th>Stok</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php $pm=mysqli_query($conn,"SELECT * FROM produk ORDER BY nama_barang ASC");
                            while($rm=mysqli_fetch_array($pm)){ ?>
                                <tr><td><?= $rm['nama_barang'] ?></td><td><?= $rm['stok'] ?></td>
                                    <td><button onclick="bukaEditFull('<?= $rm['id'] ?>','<?= $rm['nama_barang'] ?>','<?= $rm['stok'] ?>','<?= $rm['modal'] ?>','<?= $rm['member'] ?>','<?= $rm['ecer'] ?>')" style="background:var(--primary); color:white; padding:5px 15px; border-radius:8px;">EDIT</button></td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="modalEditFull" class="modal">
        <div class="modal-content">
            <h3 style="border-bottom:1px solid var(--glass-border); padding-bottom:10px;">Update Produk</h3>
            <form method="POST">
                <input type="hidden" name="id_p" id="f_id">
                <label>Nama Barang:</label><input type="text" name="nama_b" id="f_nama">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div><label>Stok:</label><input type="number" name="stok_p" id="f_stok"></div>
                    <div><label>Modal:</label><input type="number" name="modal_p" id="f_modal"></div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div><label>Harga Member:</label><input type="number" name="mem_p" id="f_mem"></div>
                    <div><label>Harga Ecer:</label><input type="number" name="ecer_p" id="f_ecer"></div>
                </div>
                <div style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
                    <button type="submit" name="update_produk_full" class="btn-yes" style="padding:12px;">SIMPAN PERUBAHAN</button>
                    <button type="button" onclick="konfirmasiHapusProduk()" class="btn-danger" style="padding:12px;">HAPUS PRODUK INI</button>
                    <button type="button" onclick="tutupModal('modalEditFull')" class="btn-no" style="padding:12px;">BATAL</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalHapusKonfirmasi" class="modal">
        <div class="modal-content" style="text-align:center; max-width:350px;">
            <div style="font-size:60px; margin-bottom:10px;">⚠️</div>
            <h3>Hapus Permanen?</h3>
            <p style="opacity:0.7;">Data produk akan hilang selamanya.</p>
            <form method="POST">
                <input type="hidden" name="id_p_hapus" id="id_hapus_input">
                <div style="display:flex; gap:12px; justify-content:center; margin-top:25px;">
                    <button type="submit" name="hapus_produk_aksi" class="btn-danger" style="width:100px;">HAPUS</button>
                    <button type="button" onclick="tutupModal('modalHapusKonfirmasi')" class="btn-no" style="width:100px;">BATAL</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalSimpanFaktur" class="modal">
        <div class="modal-content" style="text-align:center; max-width:350px;">
            <div style="font-size:60px; margin-bottom:10px;">💾</div>
            <h3>Simpan Faktur?</h3>
            <div style="display:flex; gap:12px; justify-content:center; margin-top:25px;">
                <button onclick="document.getElementById('submit_faktur').click()" class="btn-yes" style="width:120px;">YA</button>
                <button onclick="tutupModal('modalSimpanFaktur')" class="btn-no" style="width:120px;">TIDAK</button>
            </div>
        </div>
    </div>

    <div id="modalLogout" class="modal">
        <div class="modal-content" style="text-align:center; max-width:350px;">
            <div style="font-size:60px; margin-bottom:10px;">👋</div>
            <h3>Keluar Aplikasi?</h3>
            <div style="display:flex; gap:12px; justify-content:center; margin-top:25px;">
                <a href="logout.php" class="btn-yes" style="text-decoration:none; display:flex; align-items:center; justify-content:center; width:120px;">YA</a>
                <button onclick="tutupModal('modalLogout')" class="btn-no" style="width:120px;">TIDAK</button>
            </div>
        </div>
    </div>

    <div id="modalDetail" class="modal">
        <div class="modal-content">
            <h3 id="det_judul" style="border-bottom:1px solid var(--glass-border); padding-bottom:10px;">Detail Laporan</h3>
            <div id="isiDetail"></div>
            <button onclick="tutupModal('modalDetail')" class="btn-no" style="width:100%; margin-top:20px;">TUTUP</button>
        </div>
    </div>

    <div id="modalDetailFaktur" class="modal">
        <div class="modal-content">
            <h3 id="det_faktur_judul" style="border-bottom:1px solid var(--glass-border); padding-bottom:10px;">Rincian Faktur</h3>
            <div id="isiDetailFaktur"></div>
            <button onclick="tutupModal('modalDetailFaktur')" class="btn-no" style="width:100%; margin-top:20px;">TUTUP</button>
        </div>
    </div>

    <div id="modalJual" class="modal">
        <div class="modal-content" style="max-width:400px;">
            <h3 id="j_judul" style="border-bottom:1px solid var(--glass-border); padding-bottom:10px;">Detail Transaksi</h3>
            <form id="formJual" method="POST">
                <input type="hidden" name="id_b" id="j_id">
                <label>Status Pembeli:</label>
                <select name="tipe_p" id="j_tipe" onchange="toggleMemberInput()">
                    <option value="ecer">👤 Umum (Ecer)</option>
                    <option value="member">⭐ Member (Khusus)</option>
                </select>
                <div id="box_member" style="display:none; background:rgba(59, 130, 246, 0.1); padding:15px; border-radius:12px; border:1px solid rgba(59, 130, 246, 0.3); margin:15px 0;">
                    <label style="color:var(--primary); font-weight:bold;">Nama Member:</label>
                    <input type="text" name="nama_pembeli" id="j_nama" placeholder="Ketik nama member..." style="background:var(--glass-bg);">
                </div>
                <label>Cetak Nota?</label>
                <select name="pilihan_cetak"><option value="tidak">❌ Tidak</option><option value="ya">🖨️ Ya, Cetak</option></select>
                <div style="display:flex; gap:12px; margin-top:25px;">
                    <button type="button" onclick="validasiLanjutkan()" class="btn-yes" style="flex:1;">BAYAR SEKARANG</button>
                    <button type="button" onclick="tutupModal('modalJual')" class="btn-no">BATAL</button>
                </div>
                <input type="submit" name="final_jual" id="submit_asli" style="display:none;">
            </form>
        </div>
    </div>

    <div id="modalMemberBaru" class="modal">
        <div class="modal-content" style="text-align:center; max-width:350px;">
            <div style="font-size:50px;">🤷‍♂️</div>
            <h3>Member Baru?</h3>
            <p>Nama ini belum terdaftar. Simpan sebagai member baru?</p>
            <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
                <button onclick="buatMemberBaru()" class="btn-yes" style="width:100px;">YA</button>
                <button onclick="tutupModal('modalMemberBaru')" class="btn-no" style="width:100px;">TIDAK</button>
            </div>
        </div>
    </div>

    <script>
        // --- 2. FITUR AUTO LOGOUT 30 DETIK (Client Side - JavaScript) ---
        let idleTime = 0;
        const idleLimit = 30; // Batas waktu dalam detik (30 Detik)

        // Fungsi ini dipanggil setiap detik
        function timerIncrement() {
            idleTime = idleTime + 1;
            // Jika tidak ada aktivitas selama 30 detik
            if (idleTime >= idleLimit) { 
                // Redirect langsung ke logout.php
                window.location.href = 'logout.php';
            }
        }

        // Reset timer ke 0 setiap kali user melakukan sesuatu
        function resetTimer() {
            idleTime = 0;
        }

        // Jalankan timer setiap 1000ms (1 detik)
        setInterval(timerIncrement, 1000); 

        // Deteksi aktivitas user (Mouse, Keyboard, Scroll, Touch)
        window.onload = resetTimer;
        window.onmousemove = resetTimer;
        window.onmousedown = resetTimer; // Klik mouse
        window.ontouchstart = resetTimer; // Sentuh layar (HP)
        window.onclick = resetTimer;      // Klik
        window.onkeypress = resetTimer;   // Ketik keyboard
        window.addEventListener('scroll', resetTimer, true); // Scrolling
        // -----------------------------------------------------------------

        // --- SCRIPT LAINNYA ---
        function startLiveClock() {
            const inputClock = document.getElementById('liveClockInput');
            function updateTime() {
                if(inputClock){
                    let date = new Date();
                    let tahun = date.getFullYear();
                    let bulan = String(date.getMonth() + 1).padStart(2, '0');
                    let tanggal = String(date.getDate()).padStart(2, '0');
                    let jam = String(date.getHours()).padStart(2, '0');
                    let menit = String(date.getMinutes()).padStart(2, '0');
                    // Format YYYY-MM-DDTHH:MM sesuai standar input datetime-local
                    inputClock.value = `${tahun}-${bulan}-${tanggal}T${jam}:${menit}`;
                }
            }
            updateTime();
            setInterval(updateTime, 1000);
        }

        function copyID(id) {
            // Menggunakan Fallback agar compatible di semua browser
            if(navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(id).then(showNotif).catch(e => alert("Gagal copy otomatis"));
            } else {
                // Fallback untuk HTTP (non-secure)
                let textArea = document.createElement("textarea");
                textArea.value = id;
                textArea.style.position = "fixed";
                textArea.style.left = "-9999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    showNotif();
                } catch (err) {
                    alert('Tidak bisa copy ID di browser ini.');
                }
                document.body.removeChild(textArea);
            }
        }

        function showNotif(){
            const msg = document.getElementById('copyMsg');
            msg.style.display = 'block';
            setTimeout(() => { msg.style.display = 'none'; }, 2000);
        }

        function toggleTheme() {
            const body = document.documentElement;
            const targetTheme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', targetTheme);
            localStorage.setItem('theme', targetTheme);
        }
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'dark'; // Default Dark agar modern
            document.documentElement.setAttribute('data-theme', savedTheme);
            startLiveClock(); 
        });

        function tambahBarisFaktur() {
            const tbody = document.querySelector("#tabelInputFaktur tbody");
            const rowCount = tbody.rows.length + 1;
            const row = `<tr><td>${rowCount}</td><td><input type="text" name="f_nama[]"></td><td><input type="number" name="f_stok[]"></td><td><input type="number" name="f_modal[]"></td><td><input type="number" name="f_member[]"></td><td><input type="number" name="f_ecer[]"></td></tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
        }

        function konfirmasiSimpanFaktur() { document.getElementById('modalSimpanFaktur').style.display = 'flex'; }
        function konfirmasiKeluar() { document.getElementById('modalLogout').style.display = 'flex'; }
        function tog(){ document.getElementById('sb').classList.toggle('active'); }
        function pg(id){ document.querySelectorAll('.page').forEach(p => p.classList.remove('show')); document.getElementById(id).classList.add('show'); if(window.innerWidth < 1000) tog(); }
        function tutupModal(id) { document.getElementById(id).style.display = 'none'; }
        
        function lihatDetail(id) {
            fetch('index.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'get_detail_laporan=1&id_harian=' + id })
            .then(res => res.text()).then(data => { document.getElementById('det_judul').innerText = "Detail ID: " + id; document.getElementById('isiDetail').innerHTML = data; document.getElementById('modalDetail').style.display = 'flex'; });
        }

        function lihatDetailFaktur(noF) {
            fetch('index.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'get_detail_faktur=1&no_faktur=' + noF })
            .then(res => res.text()).then(data => { document.getElementById('det_faktur_judul').innerText = "Rincian Faktur: " + noF; document.getElementById('isiDetailFaktur').innerHTML = data; document.getElementById('modalDetailFaktur').style.display = 'flex'; });
        }

        function tanyaBeli(id, nama) { document.getElementById('j_id').value = id; document.getElementById('j_judul').innerText = "Beli: " + nama; document.getElementById('modalJual').style.display = 'flex'; }
        function toggleMemberInput() { document.getElementById('box_member').style.display = (document.getElementById('j_tipe').value === 'member') ? 'block' : 'none'; }
        
        function validasiLanjutkan() {
            const tipe = document.getElementById('j_tipe').value;
            const nama = document.getElementById('j_nama').value;
            if(tipe === 'member'){
                if(nama.trim() === "") return alert("Isi nama member!");
                fetch('index.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'cek_member=1&nama_cari=' + encodeURIComponent(nama) })
                .then(res => res.text()).then(data => { (data.trim() === 'ada') ? document.getElementById('submit_asli').click() : document.getElementById('modalMemberBaru').style.display = 'flex'; });
            } else { document.getElementById('submit_asli').click(); }
        }

        function buatMemberBaru() {
            const nama = document.getElementById('j_nama').value;
            fetch('index.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'tambah_member_baru=1&nama_baru=' + encodeURIComponent(nama) })
            .then(() => { tutupModal('modalMemberBaru'); document.getElementById('submit_asli').click(); });
        }

        function bukaEditFull(id, n, s, m, mem, e) {
            document.getElementById('f_id').value = id; 
            document.getElementById('f_nama').value = n;
            document.getElementById('f_stok').value = s; 
            document.getElementById('f_modal').value = m;
            document.getElementById('f_mem').value = mem; 
            document.getElementById('f_ecer').value = e;
            document.getElementById('id_hapus_input').value = id;
            document.getElementById('modalEditFull').style.display = 'flex';
        }

        function konfirmasiHapusProduk() {
            document.getElementById('modalHapusKonfirmasi').style.display = 'flex';
        }

        function filterT(inp, idT, col) {
            let filter = inp.value.toUpperCase();
            let tr = document.getElementById(idT).getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName("td")[col];
                if (td) { tr[i].style.display = (td.textContent.toUpperCase().indexOf(filter) > -1) ? "" : "none"; }
            }
        }
    </script>
</body>
</html>
