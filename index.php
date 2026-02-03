<?php
session_start();
if(!isset($_SESSION['admin'])){ header("Location: login.php"); exit; }
include 'koneksi.php';

// 1. Variabel ID Harian Otomatis (Format: ID-YYYYMMDD)
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
    echo "<div style='background:#dcfce7; color:#166534; padding:15px; border-radius:8px; margin-bottom:15px; text-align:center;'>
            <h3 style='margin:0;'>Total Pendapatan: Rp ".number_format($hitung['total_omzet'] ?? 0)."</h3>
            <small>Total Keuntungan Bersih: Rp ".number_format($hitung['total_profit'] ?? 0)."</small>
          </div>";
    $q = mysqli_query($conn, "SELECT * FROM laporan WHERE id_harian = '$id_h'");
    echo "<table border='1' style='width:100%; border-collapse:collapse;'>
            <tr style='background:#3b82f6; color:white;'><th>Barang</th><th>Tipe</th><th>Harga</th><th>Pembeli</th></tr>";
    while($d = mysqli_fetch_array($q)){
        echo "<tr><td>{$d['nama_barang']}</td><td>{$d['tipe_pembeli']}</td><td>".number_format($d['harga_jual'])."</td><td>{$d['pembeli']}</td></tr>";
    }
    echo "</table>";
    exit;
}

// 4. Detail Data Faktur
if(isset($_POST['get_detail_faktur'])){
    $no_f = mysqli_real_escape_string($conn, $_POST['no_faktur']);
    $q = mysqli_query($conn, "SELECT * FROM history_faktur WHERE no_faktur = '$no_f'");
    echo "<table border='1' style='width:100%; border-collapse:collapse;'>
            <tr style='background:#f59e0b; color:white;'><th>Barang</th><th>Stok Masuk</th><th>Modal</th><th>Member</th><th>Ecer</th></tr>";
    while($d = mysqli_fetch_array($q)){
        echo "<tr><td>{$d['nama_barang']}</td><td>{$d['stok_masuk']}</td><td>".number_format($d['modal'])."</td><td>".number_format($d['member'])."</td><td>".number_format($d['ecer'])."</td></tr>";
    }
    echo "</table>";
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
    <title>XAILLA STORE SPERPAT</title>
    <style>
        :root { --bg-main: #ffffff; --bg-card: rgba(255, 255, 255, 0.95); --text-color: #1e293b; --border-color: #e2e8f0; --border-table: #cbd5e1; --input-bg: #f8fafc; --sidebar-bg: #1e293b; }
        [data-theme="dark"] { --bg-main: #0f172a; --bg-card: rgba(30, 41, 59, 0.95); --text-color: #f8fafc; --border-color: #334155; --border-table: #475569; --input-bg: #1e293b; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg-main); color: var(--text-color); transition: 0.3s ease; }
        
        /* CSS Sidebar Fixed */
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; left: -260px; transition: 0.3s; z-index: 10000; color: white; display: flex; flex-direction: column; box-shadow: 4px 0 15px rgba(0,0,0,0.2); }
        .sidebar.active { left: 0; }
        .top { position: fixed; top: 0; left: 0; right: 0; height: 60px; background: var(--bg-card); backdrop-filter: blur(10px); display: flex; align-items: center; padding: 0 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); z-index: 9999; border-bottom: 1px solid var(--border-color); }
        .main { padding: 20px; margin-top: 60px; min-height: 100vh; }
        .card { background: var(--bg-card); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid var(--border-color); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .page { display: none; } .show { display: block; }

        /* Style Kotak ID */
        .box-id { background: #3b82f6; color: white; padding: 15px; border-radius: 10px; margin-bottom: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: bold; transition: 0.2s; border: 1px solid rgba(255,255,255,0.1); }
        .box-id:hover { background: #2563eb; }
        .box-id:active { transform: scale(0.98); }
        #copyMsg { display: none; font-size: 0.75rem; background: #10b981; padding: 4px 10px; border-radius: 6px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border: 1px solid var(--border-table); text-align: left; }
        th { background: #3b82f6; color: white; }
        input, select, button { width: 100%; padding: 10px; margin: 5px 0; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); box-sizing: border-box; }
        
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 20000; justify-content: center; align-items: center; padding: 20px; }
        .modal-content { background: var(--bg-card); color: var(--text-color); padding: 25px; border-radius: 15px; width: 100%; max-width: 500px; max-height: 85vh; overflow-y: auto; }
        
        .btn-yes { background: #3b82f6; color: white; border: none; cursor: pointer; font-weight: bold; }
        .btn-no { background: #ef4444; color: white; border: none; cursor: pointer; }
        .btn-danger { background: #dc2626; color: white; border: none; cursor: pointer; font-weight: bold; margin-top: 10px; }
        
        .menu-item { padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; color: white; display: flex; align-items: center; gap: 12px; }
        .menu-item:hover { background: rgba(255,255,255,0.15); }
        .theme-toggle { padding: 15px 20px; cursor: pointer; color: #fbbf24; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    </style>
</head>
<body>

    <div class="sidebar" id="sb">
        <div style="padding:25px; text-align:center; background: #0f172a; color:#3b82f6; font-weight:900;">XAILLA STORE</div>
        <div class="theme-toggle" onclick="toggleTheme()"><span id="theme-icon">🌙</span> <span id="theme-text">Mode Gelap</span></div>
        <div class="menu-item" onclick="pg('k')">🛒 Kasir Transaksi</div>
        <div class="menu-item" onclick="pg('f')">📦 Input Faktur Baru</div>
        <div class="menu-item" onclick="pg('h')">📜 History & Arsip</div>
        <div class="menu-item" onclick="pg('m')">✏️ Kelola Produk</div>
        <div class="menu-item" onclick="konfirmasiKeluar()" style="color:#f87171; border-top: 1px solid rgba(255,255,255,0.1);">🚪 Keluar</div>
    </div>

    <div class="top">
        <span onclick="tog()" style="font-size:24px; cursor:pointer; margin-right: 20px; position: relative; z-index: 10001;">☰</span>
        <b style="color:#3b82f6; font-size: 1.2rem;">XAILLA STORE SPERPAT</b>
    </div>

    <div class="main">
        
        <div id="k" class="page show">
            <div class="card">
                <div class="box-id" onclick="copyID('<?= $id_harian_sekarang ?>')">
                    <div>
                        <span style="opacity: 0.8; font-size: 0.8rem;">ID LAPORAN HARI INI:</span><br>
                        <b style="font-size: 1.2rem;"><?= $id_harian_sekarang ?></b>
                    </div>
                    <div style="text-align:right;">
                        <span id="copyMsg">ID Tersalin!</span>
                        <div style="font-size:0.8rem; opacity:0.8;">📋 Klik Salin</div>
                    </div>
                </div>

                <input type="text" onkeyup="filterT(this,'tK', 1)" placeholder="🔍 Cari nama barang...">
                <div style="overflow-x:auto;">
                    <table id="tK">
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
                                    <td><?= $r['stok'] ?></td>
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
            <div class="card">
                <h3>📦 Input Data Barang Baru (Faktur Pembelian)</h3>
                <form id="formFaktur" method="POST">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:15px;">
                        <div><label>Nama Toko Suplayer:</label><input type="text" name="suplayer" placeholder="Contoh: Roboot" required></div>
                        <div><label>Nomer Faktur:</label><input type="text" name="no_faktur" placeholder="Contoh: 027739" required></div>
                        <div><label>Tanggal Nota:</label><input type="datetime-local" name="tgl_nota" value="<?= date('Y-m-d\TH:i') ?>" required></div>
                    </div>
                    <div style="overflow-x:auto;">
                        <table id="tabelInputFaktur">
                            <thead><tr><th>No</th><th>Nama Barang</th><th>Stok Baru</th><th>Modal</th><th>Member</th><th>Ecer</th></tr></thead>
                            <tbody>
                                <?php for($i=1; $i<=5; $i++){ ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><input type="text" name="f_nama[]" placeholder="Nama barang"></td>
                                    <td><input type="number" name="f_stok[]" placeholder="0"></td>
                                    <td><input type="number" name="f_modal[]" placeholder="0"></td>
                                    <td><input type="number" name="f_member[]" placeholder="0"></td>
                                    <td><input type="number" name="f_ecer[]" placeholder="0"></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" onclick="tambahBarisFaktur()" style="background:#64748b; color:white; width:auto; padding:8px 20px; cursor:pointer; border-radius: 8px;">+ Tambah Baris</button>
                    <div style="margin-top:25px; text-align:right;">
                        <button type="button" onclick="konfirmasiSimpanFaktur()" class="btn-yes" style="width:250px;">SIMPAN DATA FAKTUR</button>
                    </div>
                    <input type="submit" name="simpan_faktur" id="submit_faktur" style="display:none;">
                </form>
            </div>
        </div>

        <div id="h" class="page">
             <div class="card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 25px;">
                <p style="margin:0; opacity: 0.9;">Omzet Penjualan Hari Ini</p>
                <?php $t=mysqli_fetch_array(mysqli_query($conn,"SELECT SUM(harga_jual) as tot FROM laporan WHERE id_harian='$id_harian_sekarang'")); ?>
                <h1 style="margin:5px 0 0 0;">Rp <?= number_format($t['tot'] ?? 0) ?></h1>
            </div>
            <div class="card">
                <h3>📜 Arsip Laporan Harian (Kasir)</h3>
                <input type="text" onkeyup="filterT(this,'tID', 1)" placeholder="Cari ID Laporan...">
                <table id="tID">
                    <thead><tr><th>No</th><th>ID Laporan</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php $ni=1; $gi=mysqli_query($conn,"SELECT id_harian FROM laporan GROUP BY id_harian ORDER BY tanggal DESC");
                        while($ri=mysqli_fetch_array($gi)){ ?>
                            <tr><td><?= $ni++ ?></td><td><b><?= $ri['id_harian'] ?></b></td>
                                <td><button onclick="lihatDetail('<?= $ri['id_harian'] ?>')" style="background:#10b981; color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer;">Buka Data</button></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h3>📦 History Faktur (Pembelian)</h3>
                <input type="text" onkeyup="filterT(this,'tFakt', 1)" placeholder="Cari No Faktur...">
                <table id="tFakt">
                    <thead><tr><th>No</th><th>No Faktur</th><th>Supplier</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php $nf=1; $gf=mysqli_query($conn,"SELECT no_faktur, suplayer FROM history_faktur GROUP BY no_faktur ORDER BY tanggal_input DESC");
                        while($rf=mysqli_fetch_array($gf)){ ?>
                            <tr><td><?= $nf++ ?></td><td><b><?= $rf['no_faktur'] ?></b></td><td><?= $rf['suplayer'] ?></td>
                                <td><button onclick="lihatDetailFaktur('<?= $rf['no_faktur'] ?>')" style="background:#f59e0b; color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer;">Buka Data</button></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="m" class="page">
            <div class="card">
                <h3>✏️ Edit Stok & Harga</h3>
                <input type="text" onkeyup="filterT(this,'tM', 1)" placeholder="Cari barang...">
                <table id="tM">
                    <thead><tr><th>Produk</th><th>Stok</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php $pm=mysqli_query($conn,"SELECT * FROM produk ORDER BY nama_barang ASC");
                        while($rm=mysqli_fetch_array($pm)){ ?>
                            <tr><td><?= $rm['nama_barang'] ?></td><td><?= $rm['stok'] ?></td>
                                <td><button onclick="bukaEditFull('<?= $rm['id'] ?>','<?= $rm['nama_barang'] ?>','<?= $rm['stok'] ?>','<?= $rm['modal'] ?>','<?= $rm['member'] ?>','<?= $rm['ecer'] ?>')" style="background:#3b82f6; color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer;">EDIT</button></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modalEditFull" class="modal">
        <div class="modal-content">
            <h3>Update Produk</h3>
            <form method="POST">
                <input type="hidden" name="id_p" id="f_id">
                <label>Nama Barang:</label><input type="text" name="nama_b" id="f_nama">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div><label>Stok:</label><input type="number" name="stok_p" id="f_stok"></div>
                    <div><label>Modal:</label><input type="number" name="modal_p" id="f_modal"></div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div><label>Harga Member:</label><input type="number" name="mem_p" id="f_mem"></div>
                    <div><label>Harga Ecer:</label><input type="number" name="ecer_p" id="f_ecer"></div>
                </div>
                <button type="submit" name="update_produk_full" class="btn-yes">SIMPAN</button>
                <button type="button" onclick="konfirmasiHapusProduk()" class="btn-danger">HAPUS PRODUK</button>
                <button type="button" onclick="tutupModal('modalEditFull')" class="btn-no">BATAL</button>
            </form>
        </div>
    </div>

    <div id="modalHapusKonfirmasi" class="modal">
        <div class="modal-content" style="text-align:center; max-width:400px;">
            <div style="font-size:50px; color:#dc2626;">⚠️</div>
            <h3>Hapus Produk?</h3>
            <p>Yakin ingin menghapus produk ini secara permanen?</p>
            <form method="POST">
                <input type="hidden" name="id_p_hapus" id="id_hapus_input">
                <div style="display:flex; gap:12px; justify-content:center; margin-top:20px;">
                    <button type="submit" name="hapus_produk_aksi" class="btn-yes" style="background:#dc2626;">YA, HAPUS</button>
                    <button type="button" onclick="tutupModal('modalHapusKonfirmasi')" class="btn-no" style="background:#64748b;">TIDAK</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalSimpanFaktur" class="modal">
        <div class="modal-content" style="text-align:center; max-width:400px;">
            <div style="font-size:50px;">💾</div>
            <h3>Simpan Data Faktur?</h3>
            <div style="display:flex; gap:12px; justify-content:center; margin-top:20px;">
                <button onclick="document.getElementById('submit_faktur').click()" class="btn-yes" style="width:120px;">YA</button>
                <button onclick="tutupModal('modalSimpanFaktur')" class="btn-no" style="width:120px;">TIDAK</button>
            </div>
        </div>
    </div>

    <div id="modalLogout" class="modal">
        <div class="modal-content" style="text-align:center; max-width:400px;">
            <div style="font-size:50px;">🚪</div>
            <h3>Yakin ingin keluar?</h3>
            <div style="display:flex; gap:12px; justify-content:center; margin-top:20px;">
                <a href="logout.php" class="btn-yes" style="text-decoration:none; display:flex; align-items:center; justify-content:center; width:120px;">YA</a>
                <button onclick="tutupModal('modalLogout')" class="btn-no" style="width:120px;">TIDAK</button>
            </div>
        </div>
    </div>

    <div id="modalDetail" class="modal">
        <div class="modal-content"><h3 id="det_judul">Detail Laporan</h3><div id="isiDetail"></div><button onclick="tutupModal('modalDetail')" class="btn-no" style="margin-top:15px;">TUTUP</button></div>
    </div>

    <div id="modalDetailFaktur" class="modal">
        <div class="modal-content"><h3 id="det_faktur_judul">Rincian Faktur</h3><div id="isiDetailFaktur"></div><button onclick="tutupModal('modalDetailFaktur')" class="btn-no" style="margin-top:15px;">TUTUP</button></div>
    </div>

    <div id="modalJual" class="modal">
        <div class="modal-content" style="max-width:450px;">
            <h3 id="j_judul">Detail Transaksi</h3>
            <form id="formJual" method="POST">
                <input type="hidden" name="id_b" id="j_id">
                <label>Status Pembeli:</label>
                <select name="tipe_p" id="j_tipe" onchange="toggleMemberInput()">
                    <option value="ecer">Ecer (Umum)</option>
                    <option value="member">Member (Khusus)</option>
                </select>
                <div id="box_member" style="display:none; background:#eff6ff; padding:10px; border-radius:10px; border:1px solid #bfdbfe; margin:10px 0;">
                    <label>Nama Member:</label>
                    <input type="text" name="nama_pembeli" id="j_nama" placeholder="Cari nama member...">
                </div>
                <label>Cetak Nota Resi?</label>
                <select name="pilihan_cetak"><option value="tidak">Tidak</option><option value="ya">Ya (Print)</option></select>
                <div style="display:flex; gap:12px; margin-top:25px;">
                    <button type="button" onclick="validasiLanjutkan()" class="btn-yes">BAYAR</button>
                    <button type="button" onclick="tutupModal('modalJual')" class="btn-no">BATAL</button>
                </div>
                <input type="submit" name="final_jual" id="submit_asli" style="display:none;">
            </form>
        </div>
    </div>

    <div id="modalMemberBaru" class="modal">
        <div class="modal-content" style="text-align:center;">
            <h3>Member Tidak Ada!</h3><p>Buat baru?</p>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button onclick="buatMemberBaru()" class="btn-yes">YA</button><button onclick="tutupModal('modalMemberBaru')" class="btn-no">TIDAK</button>
            </div>
        </div>
    </div>

    <script>
        function copyID(id) {
            navigator.clipboard.writeText(id).then(() => {
                const msg = document.getElementById('copyMsg');
                msg.style.display = 'block';
                setTimeout(() => { msg.style.display = 'none'; }, 2000);
            });
        }

        function toggleTheme() {
            const body = document.documentElement;
            const targetTheme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', targetTheme);
            localStorage.setItem('theme', targetTheme);
        }
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
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
