<?php
// --- 0. PAKSA HTTPS (Agar Fitur Bluetooth & Share Jalan) ---
if ((!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") && (isset($_SERVER['HTTP_HOST']))) {
    $redirect_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirect_url");
    exit;
}

session_start();

// --- 1. FITUR KEAMANAN: AUTO LOGOUT 2 MENIT ---
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 120)) {
    session_unset();     
    session_destroy();   
    header("Location: login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time(); 
// -------------------------------------------------------------

if(!isset($_SESSION['admin'])){ header("Location: login.php"); exit; }
include 'koneksi.php';

$id_harian_sekarang = "ID-" . date('Ymd');

// --- LOAD KONFIGURASI STRUK ---
$file_config = 'struk_config.json';
if(file_exists($file_config)){
    $struk_cfg = json_decode(file_get_contents($file_config), true);
} else {
    $struk_cfg = [
        'nama_toko' => 'XAILLA STORE',
        'alamat' => 'Sparepart HP, Tools & Accessories',
        'disclaimer' => "Garansi Tes Fungsi (LCD/TS):\n- Segel & Plastik pelindung UTUH.\n- Fisik tidak cacat.\n\nSUDAH DIPASANG = GARANSI VOID",
        'footer' => 'Barang yang dibeli tidak dapat ditukar.'
    ];
}

// --- AJAX HANDLER ---

// Simpan Config Struk
if(isset($_POST['simpan_struk_config'])){
    $new_cfg = [
        'nama_toko' => $_POST['cfg_nama'],
        'alamat' => $_POST['cfg_alamat'],
        'disclaimer' => $_POST['cfg_disclaimer'],
        'footer' => $_POST['cfg_footer']
    ];
    file_put_contents($file_config, json_encode($new_cfg));
    echo "<script>alert('Konfigurasi Tersimpan!'); window.location='index.php';</script>"; exit;
}

// 1. Cek Member
if(isset($_POST['cek_member'])){
    $nama = mysqli_real_escape_string($conn, $_POST['nama_cari']);
    $q = mysqli_query($conn, "SELECT * FROM member WHERE nama_member = '$nama'");
    if(mysqli_num_rows($q) > 0){ echo "ada"; } else { echo "tidak_ada"; }
    exit;
}

// 2. Tambah Member
if(isset($_POST['tambah_member_baru'])){
    $nama = mysqli_real_escape_string($conn, $_POST['nama_baru']);
    $cek = mysqli_query($conn, "SELECT * FROM member WHERE nama_member = '$nama'");
    if(mysqli_num_rows($cek) > 0){
        echo "sudah_ada";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO member (nama_member) VALUES ('$nama')");
        echo $insert ? "sukses" : "gagal";
    }
    exit;
}

// 3. Detail Laporan
if(isset($_POST['get_detail_laporan'])){
    $id_h = mysqli_real_escape_string($conn, $_POST['id_harian']);
    $q_hitung = mysqli_query($conn, "SELECT SUM(harga_jual) as total_omzet, SUM(untung) as total_profit FROM laporan WHERE id_harian = '$id_h'");
    $hitung = mysqli_fetch_array($q_hitung);
    echo "<div style='background:rgba(22, 101, 52, 0.2); color:#22c55e; padding:20px; border-radius:12px; margin-bottom:20px; text-align:center; border:1px solid rgba(34, 197, 94, 0.3);'>
            <h3 style='margin:0; font-size:1.5rem;'>Rp ".number_format($hitung['total_omzet'] ?? 0)."</h3>
            <small>Profit: Rp ".number_format($hitung['total_profit'] ?? 0)."</small>
          </div>";
    $q = mysqli_query($conn, "SELECT * FROM laporan WHERE id_harian = '$id_h'");
    echo "<div style='overflow-x:auto;'><table class='glass-table'><thead><tr><th>Barang</th><th>Tipe</th><th>Harga</th><th>Pembeli</th></tr></thead><tbody>";
    while($d = mysqli_fetch_array($q)){
        echo "<tr><td>{$d['nama_barang']}</td><td>{$d['tipe_pembeli']}</td><td>".number_format($d['harga_jual'])."</td><td>{$d['pembeli']}</td></tr>";
    }
    echo "</tbody></table></div>";
    exit;
}

// 4. Detail Faktur
if(isset($_POST['get_detail_faktur'])){
    $no_f = mysqli_real_escape_string($conn, $_POST['no_faktur']);
    $q = mysqli_query($conn, "SELECT * FROM history_faktur WHERE no_faktur = '$no_f'");
    $data = [];
    while($row = mysqli_fetch_assoc($q)){ $data[] = $row; }
    
    if(count($data) > 0){
        $head = $data[0];
        $diskon_global = $head['diskon_global'] ?? 0;
        echo "<div style='background: rgba(59, 130, 246, 0.08); padding: 15px; border-radius: 12px; margin-bottom: 25px;'>
                <b>{$head['suplayer']}</b><br><small>".date('d M Y, H:i', strtotime($head['tanggal_input']))."</small>
              </div>";
        echo "<div style='overflow-x:auto;'><table class='glass-table'><thead><tr><th>Barang</th><th>Qty</th><th>Modal</th><th>Total</th></tr></thead><tbody>";
        $total_kotor = 0;
        foreach($data as $d){
             $sub = $d['stok_masuk'] * $d['modal'];
             $total_kotor += $sub;
             echo "<tr><td>{$d['nama_barang']}</td><td>{$d['stok_masuk']}</td><td>".number_format($d['modal'])."</td><td>".number_format($sub)."</td></tr>";
        }
        echo "</tbody></table></div>";
        echo "<div style='text-align:right; margin-top:15px;'>
                Total: <b>Rp ".number_format($total_kotor)."</b><br>
                Diskon: <b style='color:red;'>- Rp ".number_format($diskon_global)."</b><br>
                <h3 style='margin-top:5px; color:var(--primary);'>Bayar: Rp ".number_format($total_kotor - $diskon_global)."</h3>
              </div>";
    }
    exit;
}

// 5. Simpan Faktur
if(isset($_POST['simpan_faktur'])){
    $suplayer = mysqli_real_escape_string($conn, $_POST['suplayer']);
    $no_faktur = mysqli_real_escape_string($conn, $_POST['no_faktur']);
    $tgl_nota = mysqli_real_escape_string($conn, $_POST['tgl_nota']);
    $global_voucher = (int)$_POST['global_voucher'];
    
    $nama_barang = $_POST['f_nama'];
    $stok_baru = $_POST['f_stok'];
    $modal = $_POST['f_modal'];
    $member = $_POST['f_member'];
    $ecer = $_POST['f_ecer'];

    for($i=0; $i < count($nama_barang); $i++){
        $nb = mysqli_real_escape_string($conn, $nama_barang[$i]);
        $sb = (int)$stok_baru[$i]; $md = (int)$modal[$i]; $mb = (int)$member[$i]; $ec = (int)$ecer[$i];
        if(!empty($nb)){
            mysqli_query($conn, "INSERT INTO history_faktur (no_faktur, suplayer, tgl_nota, nama_barang, stok_masuk, modal, member, ecer, diskon_global, tanggal_input) VALUES ('$no_faktur', '$suplayer', '$tgl_nota', '$nb', '$sb', '$md', '$mb', '$ec', '$global_voucher', NOW())");
            $cek = mysqli_query($conn, "SELECT id, stok FROM produk WHERE nama_barang = '$nb'");
            if(mysqli_num_rows($cek) > 0){
                $r = mysqli_fetch_array($cek);
                mysqli_query($conn, "UPDATE produk SET stok = stok + $sb, modal = '$md', member = '$mb', ecer = '$ec' WHERE id = '{$r['id']}'");
            } else {
                mysqli_query($conn, "INSERT INTO produk (nama_barang, modal, member, ecer, stok) VALUES ('$nb', '$md', '$mb', '$ec', '$sb')");
            }
        }
    }
    header("Location: index.php"); exit;
}

// 6. Update Produk
if(isset($_POST['update_produk_full'])){
    $id = mysqli_real_escape_string($conn, $_POST['id_p']);
    $n = mysqli_real_escape_string($conn, $_POST['nama_b']);
    $m = (int)$_POST['modal_p']; $mem = (int)$_POST['mem_p']; $e = (int)$_POST['ecer_p']; $s = (int)$_POST['stok_p'];
    mysqli_query($conn, "UPDATE produk SET nama_barang='$n', modal='$m', member='$mem', ecer='$e', stok='$s' WHERE id='$id'");
    header("Location: index.php"); exit;
}

// 7. Hapus Produk
if(isset($_POST['hapus_produk_aksi'])){
    $id = mysqli_real_escape_string($conn, $_POST['id_p_hapus']);
    mysqli_query($conn, "DELETE FROM produk WHERE id='$id'");
    header("Location: index.php"); exit;
}

// 8. TRANSAKSI KASIR (AJAX MODE)
if(isset($_POST['ajax_jual'])){
    $id_b = mysqli_real_escape_string($conn, $_POST['id_b']);
    $tipe = $_POST['tipe_p'];
    $nama_inp = mysqli_real_escape_string($conn, $_POST['nama_pembeli']);
    $pembeli = ($tipe == 'ecer') ? 'Ecer' : $nama_inp;
    
    $res = mysqli_query($conn, "SELECT * FROM produk WHERE id='$id_b'");
    if($b = mysqli_fetch_array($res)){
        
        // Cek Stok
        if($b['stok'] <= 0) {
            echo json_encode(['status' => 'stok_habis']);
            exit;
        }

        $harga = ($tipe == 'member') ? $b['member'] : $b['ecer'];
        $stok_sisa = $b['stok'] - 1;
        $untung = $harga - $b['modal'];
        
        // Simpan
        mysqli_query($conn, "INSERT INTO laporan (id_harian, nama_barang, tipe_pembeli, harga_jual, untung, modal_asli, stok_sisa, pembeli, tanggal) VALUES ('$id_harian_sekarang', '{$b['nama_barang']}', '$tipe', '$harga', '$untung', '{$b['modal']}', '$stok_sisa', '$pembeli', NOW())");
        $last_id = mysqli_insert_id($conn);
        
        // Update Stok
        mysqli_query($conn, "UPDATE produk SET stok = $stok_sisa WHERE id='$id_b'");
        
        // Return JSON
        echo json_encode([
            'status' => 'sukses',
            'id_transaksi' => $last_id,
            'nama_barang' => $b['nama_barang'],
            'harga' => $harga,
            'pembeli' => $pembeli,
            'tanggal' => date('d/m/Y H:i')
        ]);
    } else {
        echo json_encode(['status' => 'gagal']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XAILLA STORE - POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-gradient: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%); --glass-bg: rgba(255, 255, 255, 0.45); --glass-border: rgba(255, 255, 255, 0.6); --text-color: #1e293b; --text-muted: #64748b; --input-bg: rgba(255, 255, 255, 0.5); --primary: #3b82f6; --sidebar-glass: rgba(255, 255, 255, 0.65); }
        [data-theme="dark"] { --bg-gradient: linear-gradient(135deg, #0f172a 0%, #312e81 100%); --glass-bg: rgba(15, 23, 42, 0.6); --glass-border: rgba(255, 255, 255, 0.1); --text-color: #f1f5f9; --text-muted: #94a3b8; --input-bg: rgba(0, 0, 0, 0.3); --primary: #6366f1; --sidebar-glass: rgba(15, 23, 42, 0.75); }
        * { box-sizing: border-box; } body { margin: 0; font-family: 'Poppins', sans-serif; background: var(--bg-gradient); background-attachment: fixed; color: var(--text-color); transition: 0.3s ease; min-height: 100vh; }
        .glass-panel { background: var(--glass-bg); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: 16px; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1); padding: 25px; margin-bottom: 25px; }
        .glass-sidebar { width: 280px; height: 100vh; position: fixed; left: -280px; top: 0; background: var(--sidebar-glass); backdrop-filter: blur(16px); border-right: 1px solid var(--glass-border); z-index: 10000; transition: 0.4s; display: flex; flex-direction: column; padding-top: 20px; }
        .glass-sidebar.active { left: 0; }
        .glass-topbar { position: fixed; top: 15px; left: 15px; right: 15px; height: 70px; background: var(--glass-bg); backdrop-filter: blur(12px); border-radius: 16px; border: 1px solid var(--glass-border); display: flex; align-items: center; padding: 0 25px; z-index: 9999; justify-content: space-between; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .main { margin-top: 100px; padding: 0 15px 20px 15px; max-width: 1200px; margin-left: auto; margin-right: auto; }
        input, select, button, textarea { width: 100%; padding: 12px 15px; margin: 8px 0; border-radius: 12px; border: 1px solid var(--glass-border); background: var(--input-bg); color: var(--text-color); font-family: 'Poppins', sans-serif; font-size: 0.95rem; outline: none; transition: 0.3s; }
        button { font-weight: 600; cursor: pointer; border: none; text-transform: uppercase; } .btn-yes { background: var(--primary); color: white; } .btn-no { background: rgba(100, 116, 139, 0.5); color: white; } .btn-danger { background: #ef4444; color: white; }
        .menu-item { padding: 15px 25px; cursor: pointer; color: var(--text-color); display: flex; align-items: center; gap: 15px; font-weight: 500; transition: 0.2s; border-left: 3px solid transparent; margin: 2px 10px; border-radius: 8px; }
        .menu-item:hover { background: rgba(255,255,255,0.1); border-left-color: var(--primary); padding-left: 30px; }
        .theme-switch-wrapper { margin: 20px; padding: 10px 15px; background: rgba(0,0,0,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; border: 1px solid var(--glass-border); }
        .theme-switch-track { width: 46px; height: 24px; background: #cbd5e1; border-radius: 50px; position: relative; transition: 0.3s; }
        .theme-switch-handle { width: 18px; height: 18px; background: white; border-radius: 50%; position: absolute; top: 3px; left: 3px; transition: 0.3s; display: flex; align-items: center; justify-content: center; font-size: 10px; }
        [data-theme="dark"] .theme-switch-track { background: var(--primary); } [data-theme="dark"] .theme-switch-handle { left: 25px; background: #1e293b; color: #fbbf24; }
        .glass-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; margin-top: 10px; } .glass-table th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 0.85rem; border-bottom: 1px solid var(--glass-border); } .glass-table td { padding: 15px; background: rgba(255,255,255,0.05); } .glass-table td:first-child { border-radius: 10px 0 0 10px; } .glass-table td:last-child { border-radius: 0 10px 10px 0; }
        .box-id { background: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%); color: white; padding: 20px; border-radius: 16px; margin-bottom: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.5); position: relative; overflow: hidden; }
        
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); z-index: 20000; justify-content: center; align-items: center; padding: 20px; animation: fadeIn 0.3s; }
        .modal-content { background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); color: var(--text-color); padding: 30px; border-radius: 20px; width: 100%; max-width: 500px; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3); animation: slideUp 0.3s; }
        .page { display: none; animation: fadeIn 0.4s ease-out; } .show { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } } @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .struk-preview { background: white; color: black; font-family: 'Courier New', monospace; padding: 10px; border: 1px solid #ccc; font-size: 11px; min-height: 400px; }
        
        /* TOMBOL MENU SHARE/PRINT */
        .print-option-btn {
            display: flex; align-items: center; gap: 15px;
            padding: 15px 20px; width: 100%; margin-bottom: 12px;
            border-radius: 12px; border: 1px solid var(--glass-border);
            background: rgba(255,255,255,0.05); color: var(--text-color);
            font-size: 1rem; font-weight: 500; cursor: pointer; transition: 0.2s;
            text-align: left;
        }
        .print-option-btn:hover { background: rgba(59, 130, 246, 0.2); border-color: var(--primary); transform: translateX(5px); }
        .print-option-btn span { font-size: 1.5rem; }
        
        .progress-container { width: 100%; background-color: rgba(255,255,255,0.2); border-radius: 10px; margin: 15px 0; overflow: hidden; }
        .progress-bar { width: 0%; height: 8px; background: linear-gradient(90deg, #3b82f6, #8b5cf6); transition: width 0.1s; }
        
        /* STYLE UNTUK STOK HABIS */
        .row-habis { opacity: 0.5; background: rgba(239, 68, 68, 0.1); pointer-events: none; }
        .row-habis:hover { transform: none !important; }
        .badge-habis { background: #ef4444; color: white; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; }
    </style>
</head>
<body>

    <div class="glass-sidebar" id="sb">
        <div style="padding: 25px;">
            <div style="font-size: 1.5rem; font-weight: 700; color:var(--primary);">XAILLA</div>
            <div style="font-size: 0.8rem; opacity: 0.7;">STORE DASHBOARD</div>
        </div>
        <div class="menu-item" onclick="pg('k')"><span>🛒</span> Kasir Transaksi</div>
        <div class="menu-item" onclick="pg('f')"><span>📦</span> Input Faktur Baru</div>
        <div class="menu-item" onclick="pg('h')"><span>📜</span> History & Arsip</div>
        <div class="menu-item" onclick="pg('m')"><span>✏️</span> Kelola Produk</div>
        <div class="menu-item" onclick="pg('s')"><span>🧾</span> Edit Struk</div>
        
        <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
            <div class="theme-switch-wrapper" onclick="toggleTheme()">
                <div style="font-size:0.9rem; font-weight:600;">Mode Gelap</div>
                <div class="theme-switch-track"><div class="theme-switch-handle" id="theme-icon-handle">☀️</div></div>
            </div>
            <div class="menu-item" onclick="konfirmasiKeluar()" style="color:#f87171;"><span>🚪</span> Keluar</div>
        </div>
    </div>

    <div class="glass-topbar">
        <div style="display:flex; align-items:center;">
            <span onclick="tog()" style="font-size:24px; cursor:pointer; margin-right: 20px; color: var(--primary);">☰</span>
            <b>Store Management</b>
        </div>
    </div>

    <div class="main">
        <div id="k" class="page show">
            <div class="box-id" onclick="copyID('<?= $id_harian_sekarang ?>')">
                <b><?= $id_harian_sekarang ?></b><span id="copyMsg" style="display:none; float:right;">Disalin!</span>
            </div>
            <div class="glass-panel">
                <input type="text" onkeyup="filterT(this,'tK', 1)" placeholder="🔍 Cari barang...">
                <div style="overflow-x:auto;">
                    <table id="tK" class="glass-table">
                        <thead><tr><th>No</th><th>Barang</th><th>Stok</th><th>Modal</th><th>Member</th><th>Ecer</th></tr></thead>
                        <tbody>
                            <?php 
                            $no=1; 
                            $p=mysqli_query($conn,"SELECT * FROM produk ORDER BY nama_barang ASC");
                            while($r=mysqli_fetch_array($p)){ 
                                $isHabis = ($r['stok'] <= 0);
                                $rowClass = $isHabis ? "row-habis" : "";
                                $clickEvent = $isHabis ? "alert('Stok Habis!')" : "tanyaBeli('{$r['id']}', '{$r['nama_barang']}')";
                                $stokLabel = $isHabis ? "<span class='badge-habis'>HABIS</span>" : "<span style='background:rgba(99, 102, 241, 0.2); padding:2px 8px; border-radius:6px;'>{$r['stok']}</span>";
                            ?>
                                <tr class="<?= $rowClass ?>" onclick="<?= $clickEvent ?>" style="<?= $isHabis ? '' : 'cursor:pointer;' ?>">
                                    <td><?= $no++ ?></td>
                                    <td><b><?= $r['nama_barang'] ?></b></td>
                                    <td><?= $stokLabel ?></td>
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
                <h3>📦 Input Faktur Baru</h3>
                <form id="formFaktur" method="POST">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px;">
                        <div><label>Suplayer</label><input type="text" name="suplayer" required></div>
                        <div><label>No. Faktur</label><input type="text" name="no_faktur" required></div>
                        <div><label>Waktu</label><input type="datetime-local" name="tgl_nota" id="liveClockInput"></div>
                    </div>
                    <input type="hidden" name="global_voucher" id="hidden_voucher" value="0">
                    <div style="overflow-x:auto; margin-top:15px;">
                        <table id="tabelInputFaktur" class="glass-table">
                            <thead><tr><th>No</th><th>Barang</th><th>Qty</th><th>Modal</th><th>Member</th><th>Ecer</th></tr></thead>
                            <tbody>
                                <?php for($i=1; $i<=5; $i++){ ?>
                                <tr><td><?= $i ?></td><td><input type="text" name="f_nama[]"></td><td><input type="number" name="f_stok[]" class="inp-stok" onkeyup="hitungTotalSemua()"></td><td><input type="number" name="f_modal[]" class="inp-modal" onkeyup="hitungTotalSemua()"></td><td><input type="number" name="f_member[]"></td><td><input type="number" name="f_ecer[]"></td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align:right; margin-top:20px;">
                        <div>Total Aset: <b id="txt_total_kotor">0</b></div>
                        <div>Voucher: <input type="number" id="inp_voucher" onkeyup="hitungTotalSemua()" style="width:100px; display:inline-block;"></div>
                        <h3 style="color:var(--primary);">Bayar: <span id="txt_grand_total">0</span></h3>
                    </div>
                    <div style="margin-top:15px;"><button type="button" onclick="tambahBarisFaktur()">+ Baris</button> <button type="button" onclick="konfirmasiSimpanFaktur()" class="btn-yes">SIMPAN</button></div>
                    <input type="submit" name="simpan_faktur" id="submit_faktur" style="display:none;">
                </form>
            </div>
        </div>

        <div id="h" class="page">
             <div class="glass-panel" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                <?php $t=mysqli_fetch_array(mysqli_query($conn,"SELECT SUM(harga_jual) as tot FROM laporan WHERE id_harian='$id_harian_sekarang'")); ?>
                <h1>Rp <?= number_format($t['tot'] ?? 0) ?></h1>
                <small>Omzet Hari Ini</small>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div class="glass-panel">
                    <h3>📜 Laporan Harian</h3>
                    <input type="text" onkeyup="filterT(this,'tID', 1)" placeholder="Cari ID...">
                    <div style="max-height:400px; overflow-y:auto;"><table id="tID" class="glass-table"><thead><tr><th>No</th><th>ID</th><th>Aksi</th></tr></thead><tbody><?php $ni=1; $gi=mysqli_query($conn,"SELECT id_harian FROM laporan GROUP BY id_harian ORDER BY tanggal DESC"); while($ri=mysqli_fetch_array($gi)){ echo "<tr><td>".$ni++."</td><td><b>{$ri['id_harian']}</b></td><td><button onclick=\"lihatDetail('{$ri['id_harian']}')\">Buka</button></td></tr>"; } ?></tbody></table></div>
                </div>
                <div class="glass-panel">
                    <h3>📦 History Faktur</h3>
                    <input type="text" onkeyup="filterT(this,'tFakt', 1)" placeholder="Cari Faktur...">
                    <div style="max-height:400px; overflow-y:auto;"><table id="tFakt" class="glass-table"><thead><tr><th>No</th><th>Faktur</th><th>Aksi</th></tr></thead><tbody><?php $nf=1; $gf=mysqli_query($conn,"SELECT no_faktur, suplayer FROM history_faktur GROUP BY no_faktur ORDER BY tanggal_input DESC"); while($rf=mysqli_fetch_array($gf)){ echo "<tr><td>".$nf++."</td><td><b>{$rf['no_faktur']}</b><br><small>{$rf['suplayer']}</small></td><td><button onclick=\"lihatDetailFaktur('{$rf['no_faktur']}')\">Buka</button></td></tr>"; } ?></tbody></table></div>
                </div>
            </div>
        </div>

        <div id="m" class="page">
            <div class="glass-panel">
                <h3>✏️ Kelola Produk</h3>
                <input type="text" onkeyup="filterT(this,'tM', 1)" placeholder="Cari...">
                <div style="overflow-x:auto;">
                    <table id="tM" class="glass-table">
                        <thead><tr><th>Produk</th><th>Stok</th><th>Aksi</th></tr></thead>
                        <tbody><?php $pm=mysqli_query($conn,"SELECT * FROM produk ORDER BY nama_barang ASC"); while($rm=mysqli_fetch_array($pm)){ echo "<tr><td>{$rm['nama_barang']}</td><td>{$rm['stok']}</td><td><button onclick=\"bukaEditFull('{$rm['id']}','{$rm['nama_barang']}','{$rm['stok']}','{$rm['modal']}','{$rm['member']}','{$rm['ecer']}')\" class='btn-yes'>EDIT</button></td></tr>"; } ?></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="s" class="page">
            <div class="glass-panel">
                <h3>🧾 Edit Tampilan Struk</h3>
                <div style="display:grid; grid-template-columns: 1fr 350px; gap:20px;">
                    <form method="POST">
                        <label>Nama Toko</label><input type="text" name="cfg_nama" id="i_nama" value="<?= $struk_cfg['nama_toko'] ?>" onkeyup="livePreview()">
                        <label>Alamat</label><input type="text" name="cfg_alamat" id="i_alamat" value="<?= $struk_cfg['alamat'] ?>" onkeyup="livePreview()">
                        <label>Syarat Garansi</label><textarea name="cfg_disclaimer" id="i_disc" rows="5" onkeyup="livePreview()"><?= $struk_cfg['disclaimer'] ?></textarea>
                        <label>Footer</label><input type="text" name="cfg_footer" id="i_footer" value="<?= $struk_cfg['footer'] ?>" onkeyup="livePreview()">
                        <button type="submit" name="simpan_struk_config" class="btn-yes" style="margin-top:15px;">SIMPAN</button>
                    </form>
                    <div class="struk-preview">
                        <div style="text-align:center;">
                            <b style="font-size:16px;" id="p_nama">XAILLA STORE</b><br>
                            <span style="font-size:10px;" id="p_alamat">...</span><br>
                            --------------------------------
                        </div>
                        <div>22/02/2026 14:30<br>LCD OPPO A3S<br>1 x 150,000 = 150,000<br>--------------------------------<br><b>TOTAL: 150,000</b><br>--------------------------------</div>
                        <div style="text-align:center; margin-top:5px;"><b>SYARAT GARANSI</b></div>
                        <div style="font-size:9px; border:1px solid #000; padding:2px;" id="p_disc">...</div>
                        <div style="text-align:center; margin-top:10px;" id="p_footer">...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modalJual" class="modal">
        <div class="modal-content" style="max-width:400px;">
            <h3>Detail Transaksi</h3>
            <input type="hidden" id="j_id">
            <label>Tipe Pembeli:</label>
            <select id="j_tipe" onchange="toggleMemberInput()"><option value="ecer">Umum (Ecer)</option><option value="member">Member</option></select>
            <div id="box_member" style="display:none;"><label>Nama:</label><input type="text" id="j_nama"></div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button onclick="prosesBayar()" class="btn-yes" style="flex:1;">BAYAR SEKARANG</button>
                <button onclick="tutupModal('modalJual')" class="btn-no">BATAL</button>
            </div>
        </div>
    </div>

    <div id="modalKonfirmasiBayar" class="modal" style="z-index: 20002;">
        <div class="modal-content" style="text-align:center; max-width:350px;">
            <div style="font-size:50px;">🛒</div>
            <h3>Lanjutkan Pembayaran?</h3>
            <p>Pastikan data transaksi sudah benar.</p>
            <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
                <button onclick="konfirmasiBayarYa()" class="btn-yes">YA, PROSES</button>
                <button onclick="tutupModal('modalKonfirmasiBayar')" class="btn-no">BATAL</button>
            </div>
        </div>
    </div>

    <div id="modalPrintOption" class="modal" style="z-index: 20005;">
        <div class="modal-content" style="text-align:center; max-width:450px;">
            <div style="font-size:50px;">✅</div>
            <h3>Transaksi Sukses!</h3>
            <p style="opacity:0.7; margin-bottom:20px;">Silakan pilih metode cetak atau bagikan struk:</p>
            <input type="hidden" id="print_transaksi_id">
            
            <div class="print-option-btn" onclick="printViaUSB()">
                <span>🖨️</span> 
                <div>
                    <div>Printer Kabel (USB)</div>
                    <small style="opacity:0.6; font-size:0.75rem;">Deteksi otomatis printer bawaan</small>
                </div>
            </div>
            
            <div class="print-option-btn" onclick="printViaBluetooth()">
                <span>🔵</span> 
                <div>
                    <div>Printer Bluetooth</div>
                    <small style="opacity:0.6; font-size:0.75rem;">Scan & Connect Thermal Printer</small>
                </div>
            </div>

            <div class="print-option-btn" onclick="shareStruk()">
                <span>📱</span> 
                <div>
                    <div>Bagikan (Share)</div>
                    <small style="opacity:0.6; font-size:0.75rem;">Menu Bawaan HP / WhatsApp</small>
                </div>
            </div>

            <button onclick="selesaiTransaksi()" class="btn-no" style="width:100%; margin-top:10px;">Tutup / Transaksi Baru</button>
        </div>
    </div>

    <div id="modalProsesPrint" class="modal" style="z-index: 20010;">
        <div class="modal-content" style="text-align:center; max-width:300px;">
            <h3>Sedang Memproses...</h3>
            <div id="statusPrint">Mendeteksi Printer...</div>
            <div class="progress-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>
        </div>
    </div>

    <div id="modalMemberBaru" class="modal">
        <div class="modal-content" style="text-align:center; max-width:350px;">
            <div style="font-size:40px;">🤷‍♂️</div>
            <h3>Member Tidak Ditemukan</h3>
            <p>Simpan sebagai member baru dan lanjutkan transaksi?</p>
            <div style="display:flex; gap:10px; justify-content:center; margin-top:15px;">
                <button onclick="buatMemberBaru()" class="btn-yes">YA, SIMPAN</button>
                <button onclick="tutupModal('modalMemberBaru')" class="btn-no">BATAL</button>
            </div>
        </div>
    </div>
    
    <div id="modalEditFull" class="modal"><div class="modal-content"><h3>Update Produk</h3><form method="POST"><input type="hidden" name="id_p" id="f_id"><label>Nama:</label><input type="text" name="nama_b" id="f_nama"><div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;"><div><label>Stok:</label><input type="number" name="stok_p" id="f_stok"></div><div><label>Modal:</label><input type="number" name="modal_p" id="f_modal"></div><div><label>Member:</label><input type="number" name="mem_p" id="f_mem"></div><div><label>Ecer:</label><input type="number" name="ecer_p" id="f_ecer"></div></div><button type="submit" name="update_produk_full" class="btn-yes" style="margin-top:15px; width:100%;">SIMPAN</button><button type="button" onclick="konfirmasiHapusProduk()" class="btn-danger" style="margin-top:10px; width:100%;">HAPUS</button><button type="button" onclick="tutupModal('modalEditFull')" class="btn-no" style="margin-top:10px; width:100%;">BATAL</button></form></div></div>
    <div id="modalHapusKonfirmasi" class="modal"><div class="modal-content" style="text-align:center;"><h3>Hapus Permanen?</h3><form method="POST"><input type="hidden" name="id_p_hapus" id="id_hapus_input"><button type="submit" name="hapus_produk_aksi" class="btn-danger">HAPUS</button><button type="button" onclick="tutupModal('modalHapusKonfirmasi')" class="btn-no">BATAL</button></form></div></div>
    <div id="modalSimpanFaktur" class="modal"><div class="modal-content" style="text-align:center;"><h3>Simpan Faktur?</h3><button onclick="document.getElementById('submit_faktur').click()" class="btn-yes">YA</button><button onclick="tutupModal('modalSimpanFaktur')" class="btn-no">TIDAK</button></div></div>
    <div id="modalLogout" class="modal"><div class="modal-content" style="text-align:center;"><h3>Keluar?</h3><a href="logout.php" class="btn-yes">YA</a><button onclick="tutupModal('modalLogout')" class="btn-no">TIDAK</button></div></div>
    <div id="modalDetail" class="modal"><div class="modal-content"><h3 id="det_judul">Detail</h3><div id="isiDetail"></div><button onclick="tutupModal('modalDetail')" class="btn-no" style="width:100%;">TUTUP</button></div></div>
    <div id="modalDetailFaktur" class="modal"><div class="modal-content"><h3 id="det_faktur_judul">Faktur</h3><div id="isiDetailFaktur"></div><button onclick="tutupModal('modalDetailFaktur')" class="btn-no" style="width:100%;">TUTUP</button></div></div>

    <script>
        const STORE_CFG = { nama: "<?= $struk_cfg['nama_toko'] ?>", alamat: "<?= $struk_cfg['alamat'] ?>", footer: "<?= $struk_cfg['footer'] ?>" };
        let TRANSAKSI_DATA = null;
        let DATA_BAYAR_TEMP = {}; 

        function prosesBayar() {
            const id_b = document.getElementById('j_id').value;
            const tipe = document.getElementById('j_tipe').value;
            const nama = document.getElementById('j_nama').value;
            
            DATA_BAYAR_TEMP = { id: id_b, tipe: tipe, nama: nama };

            if(tipe === 'member'){
                if(nama.trim() === "") return alert("Isi nama member!");
                fetch('index.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'cek_member=1&nama_cari=' + encodeURIComponent(nama) })
                .then(res => res.text()).then(data => { 
                    if(data.trim() !== 'ada') { document.getElementById('modalMemberBaru').style.display = 'flex'; } 
                    else { document.getElementById('modalKonfirmasiBayar').style.display = 'flex'; }
                });
            } else { document.getElementById('modalKonfirmasiBayar').style.display = 'flex'; }
        }

        function konfirmasiBayarYa() {
            tutupModal('modalKonfirmasiBayar');
            lakukanAjaxJual(DATA_BAYAR_TEMP.id, DATA_BAYAR_TEMP.tipe, DATA_BAYAR_TEMP.nama);
        }

        function lakukanAjaxJual(id, tipe, nama) {
            fetch('index.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `ajax_jual=1&id_b=${id}&tipe_p=${tipe}&nama_pembeli=${encodeURIComponent(nama)}` })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'sukses') {
                    tutupModal('modalJual');
                    TRANSAKSI_DATA = data; 
                    document.getElementById('print_transaksi_id').value = data.id_transaksi;
                    document.getElementById('modalPrintOption').style.display = 'flex';
                } else if(data.status === 'stok_habis') {
                    alert("Gagal! Stok barang habis.");
                    tutupModal('modalJual');
                    window.location.reload();
                } else { alert("Gagal memproses transaksi!"); }
            });
        }

        // --- SHARE DENGAN NATIVE SHARE SHEET (FITUR BARU) ---
        async function shareStruk() {
            if (location.protocol !== 'https:') {
                alert("Fitur Share membutuhkan koneksi HTTPS (Gembok Aman).");
                return;
            }

            const txt = `*${STORE_CFG.nama}*\n${STORE_CFG.alamat}\n\n` +
                        `Tanggal: ${TRANSAKSI_DATA.tanggal}\n` +
                        `Barang: ${TRANSAKSI_DATA.nama_barang}\n` +
                        `Harga: Rp ${parseInt(TRANSAKSI_DATA.harga).toLocaleString('id-ID')}\n` +
                        `Pembeli: ${TRANSAKSI_DATA.pembeli}\n\n` +
                        `Terima Kasih!`;

            if (navigator.share) {
                try {
                    await navigator.share({
                        title: 'Struk Belanja',
                        text: txt
                    });
                } catch (err) {
                    if (err.name !== 'AbortError') console.error('Share failed:', err);
                }
            } else {
                // Fallback jika browser tidak support native share
                navigator.clipboard.writeText(txt).then(() => {
                    if(confirm("Teks disalin ke clipboard.\nLanjut kirim ke WhatsApp?")) {
                        window.open(`https://wa.me/?text=${encodeURIComponent(txt)}`, '_blank');
                    }
                });
            }
        }

        function buatMemberBaru() {
            const nama = document.getElementById('j_nama').value;
            if(!nama) return;
            fetch('index.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'tambah_member_baru=1&nama_baru=' + encodeURIComponent(nama) })
            .then(res => res.text())
            .then(status => {
                tutupModal('modalMemberBaru');
                if(status.trim() === 'sudah_ada') { alert("Nama Member sudah ada, melanjutkan transaksi..."); }
                else if(status.trim() !== 'sukses') { alert("Gagal menyimpan member, mencoba lanjut..."); }
                DATA_BAYAR_TEMP.nama = nama; 
                document.getElementById('modalKonfirmasiBayar').style.display = 'flex';
            });
        }

        function selesaiTransaksi() { window.location.reload(); }

        function printViaUSB() {
            const id = document.getElementById('print_transaksi_id').value;
            const modalProses = document.getElementById('modalProsesPrint');
            const bar = document.getElementById('progressBar');
            const status = document.getElementById('statusPrint');
            tutupModal('modalPrintOption');
            modalProses.style.display = 'flex';
            let width = 0;
            status.innerText = "Mendeteksi Printer...";
            const interval = setInterval(() => {
                if (width >= 100) {
                    clearInterval(interval);
                    status.innerText = "Mencetak...";
                    setTimeout(() => {
                        window.open('struk.php?id=' + id, '_blank');
                        tutupModal('modalProsesPrint');
                        selesaiTransaksi(); 
                    }, 500);
                } else { width += 5; bar.style.width = width + '%'; }
            }, 50);
        }

        async function printViaBluetooth() {
            tutupModal('modalPrintOption');
            if (location.protocol !== 'https:') {
                alert("Bluetooth membutuhkan HTTPS (Gembok Aman). Aktifkan SSL di hosting Anda.");
                document.getElementById('modalPrintOption').style.display = 'flex';
                return;
            }
            if (!navigator.bluetooth) {
                alert("Browser tidak support Bluetooth. Gunakan Chrome.");
                document.getElementById('modalPrintOption').style.display = 'flex';
                return;
            }
            try {
                const device = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb'] 
                });
                
                const server = await device.gatt.connect();
                const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
                const characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');

                const encoder = new TextEncoder();
                let commands = [];
                function addText(text) { commands.push(encoder.encode(text)); }
                function addCmd(cmd) { commands.push(new Uint8Array(cmd)); }

                addCmd([0x1B, 0x40]); 
                addCmd([0x1B, 0x61, 1]); 
                addCmd([0x1B, 0x45, 1]); 
                addText(STORE_CFG.nama + "\n");
                addCmd([0x1B, 0x45, 0]); 
                addText(STORE_CFG.alamat + "\n--------------------------------\n");
                addCmd([0x1B, 0x61, 0]); 
                addText("Tgl : " + TRANSAKSI_DATA.tanggal + "\n");
                addText("Plg : " + TRANSAKSI_DATA.pembeli + "\n--------------------------------\n");
                addCmd([0x1B, 0x45, 1]); 
                addText(TRANSAKSI_DATA.nama_barang + "\n");
                addCmd([0x1B, 0x45, 0]); 
                let hargaFmt = parseInt(TRANSAKSI_DATA.harga).toLocaleString('id-ID');
                addText("1 x " + hargaFmt + " = " + hargaFmt + "\n--------------------------------\n");
                addCmd([0x1B, 0x61, 1]); 
                addCmd([0x1B, 0x45, 1]); 
                addText("TOTAL: Rp " + hargaFmt + "\n");
                addCmd([0x1B, 0x45, 0]); 
                addText("--------------------------------\nTERIMA KASIH\n" + STORE_CFG.footer + "\n\n\n");

                for (let cmd of commands) { await characteristic.writeValue(cmd); }
                alert("Berhasil Mencetak via Bluetooth!");
                device.gatt.disconnect();
                selesaiTransaksi();

            } catch (error) {
                if (error.name === 'NotFoundError') {
                    document.getElementById('modalPrintOption').style.display = 'flex';
                } else {
                    alert("Koneksi Gagal: " + error);
                    document.getElementById('modalPrintOption').style.display = 'flex';
                }
            }
        }

        function pg(id){ document.querySelectorAll('.page').forEach(p => p.classList.remove('show')); document.getElementById(id).classList.add('show'); document.getElementById('sb').classList.remove('active'); }
        function tog(){ document.getElementById('sb').classList.toggle('active'); }
        function tutupModal(id) { document.getElementById(id).style.display = 'none'; }
        
        function tanyaBeli(id, nama) { document.getElementById('j_id').value = id; document.getElementById('modalJual').style.display = 'flex'; }
        function toggleMemberInput() { document.getElementById('box_member').style.display = (document.getElementById('j_tipe').value === 'member') ? 'block' : 'none'; }
        
        let idleTime = 0; setInterval(() => { idleTime++; if(idleTime >= 120) window.location.href = 'logout.php'; }, 1000);
        window.onmousemove = window.onkeypress = () => { idleTime = 0; };
        setInterval(() => { if(document.getElementById('liveClockInput')) { const d=new Date(); document.getElementById('liveClockInput').value = new Date(d.getTime() - (d.getTimezoneOffset() * 60000)).toISOString().slice(0,16); } }, 1000);
        function toggleTheme() { const b = document.documentElement; const t = b.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'; b.setAttribute('data-theme', t); localStorage.setItem('theme', t); document.getElementById('theme-icon-handle').innerText = t === 'dark' ? '🌙' : '☀️'; }
        document.addEventListener('DOMContentLoaded', () => { const t = localStorage.getItem('theme') || 'dark'; document.documentElement.setAttribute('data-theme', t); document.getElementById('theme-icon-handle').innerText = t === 'dark' ? '🌙' : '☀️'; if(document.getElementById('i_nama')) livePreview(); });
        function copyID(t){ navigator.clipboard.writeText(t); document.getElementById('copyMsg').style.display='inline'; setTimeout(()=>document.getElementById('copyMsg').style.display='none',1000); }
        function filterT(inp, idT, col) { let f = inp.value.toUpperCase(); let tr = document.getElementById(idT).getElementsByTagName("tr"); for (let i = 1; i < tr.length; i++) { let td = tr[i].getElementsByTagName("td")[col]; if (td) { tr[i].style.display = (td.textContent.toUpperCase().indexOf(f) > -1) ? "" : "none"; } } }
        function hitungTotalSemua() { let tot = 0; const s = document.getElementsByName('f_stok[]'); const m = document.getElementsByName('f_modal[]'); for(let i=0; i<s.length; i++) tot += (parseFloat(s[i].value)||0) * (parseFloat(m[i].value)||0); let v = parseFloat(document.getElementById('inp_voucher').value)||0; document.getElementById('hidden_voucher').value = v; document.getElementById('txt_total_kotor').innerText = "Rp " + tot.toLocaleString('id-ID'); document.getElementById('txt_grand_total').innerText = "Rp " + (tot - v).toLocaleString('id-ID'); }
        function livePreview() { document.getElementById('p_nama').innerText = document.getElementById('i_nama').value; document.getElementById('p_alamat').innerText = document.getElementById('i_alamat').value; document.getElementById('p_disc').innerText = document.getElementById('i_disc').value; document.getElementById('p_footer').innerText = document.getElementById('i_footer').value; }
        function bukaEditFull(id, n, s, m, mem, e) { document.getElementById('f_id').value = id; document.getElementById('f_nama').value = n; document.getElementById('f_stok').value = s; document.getElementById('f_modal').value = m; document.getElementById('f_mem').value = mem; document.getElementById('f_ecer').value = e; document.getElementById('modalEditFull').style.display = 'flex'; }
        function konfirmasiHapusProduk() { document.getElementById('modalHapusKonfirmasi').style.display = 'flex'; }
        function konfirmasiSimpanFaktur() { document.getElementById('modalSimpanFaktur').style.display = 'flex'; }
        function konfirmasiKeluar() { document.getElementById('modalLogout').style.display = 'flex'; }
        function lihatDetail(id) { fetch('index.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'get_detail_laporan=1&id_harian=' + id }).then(res => res.text()).then(data => { document.getElementById('det_judul').innerText = "Detail " + id; document.getElementById('isiDetail').innerHTML = data; document.getElementById('modalDetail').style.display = 'flex'; }); }
        function lihatDetailFaktur(noF) { fetch('index.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'get_detail_faktur=1&no_faktur=' + noF }).then(res => res.text()).then(data => { document.getElementById('det_faktur_judul').innerText = "Faktur " + noF; document.getElementById('isiDetailFaktur').innerHTML = data; document.getElementById('modalDetailFaktur').style.display = 'flex'; }); }
        function tambahBarisFaktur() { document.querySelector("#tabelInputFaktur tbody").insertAdjacentHTML('beforeend', `<tr><td>+</td><td><input type=\"text\" name=\"f_nama[]\"></td><td><input type=\"number\" name=\"f_stok[]\" class=\"inp-stok\" onkeyup=\"hitungTotalSemua()\"></td><td><input type=\"number\" name=\"f_modal[]\" class=\"inp-modal\" onkeyup=\"hitungTotalSemua()\"></td><td><input type=\"number\" name=\"f_member[]\"></td><td><input type=\"number\" name=\"f_ecer[]\"></td></tr>`); }
    </script>
</body>
</html>
