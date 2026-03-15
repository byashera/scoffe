<?php
session_start();
$jsonFile = 'menu.json';
$configFile = 'config.json';

if (!file_exists($configFile)) { file_put_contents($configFile, json_encode(['password' => '123456'])); }
$config = json_decode(file_get_contents($configFile), true);
$password = $config['password'];

if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }

if (isset($_POST['login'])) {
    if ($_POST['pass'] == $password) { $_SESSION['admin'] = true; } else { $hata = "Hatalı şifre!"; }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: admin.php"); exit; }

if (!isset($_SESSION['admin'])) {
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Girişi</title>
    <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/1047/1047503.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { background: #F5F2EB; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: 'Poppins', sans-serif; padding: 15px; }
        .login-box { background: #fff; padding: 40px 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(74, 64, 54, 0.1); text-align: center; width: 100%; max-width: 350px; }
        .login-box h2 { color: #4A4036; margin-top: 0; margin-bottom: 25px; }
        input[type="password"] { width: 100%; padding: 12px 15px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 10px; font-family: 'Poppins', sans-serif; outline: none; }
        input[type="password"]:focus { border-color: #D48C46; }
        .btn { background: #D48C46; color: white; border: none; padding: 12px; width: 100%; border-radius: 10px; cursor: pointer; font-weight: 500; font-family: 'Poppins', sans-serif; transition: 0.3s; margin-bottom: 10px; }
        .btn-back { background: transparent; color: #888; border: 1px solid #ddd; width: 100%; padding: 12px; border-radius: 10px; cursor: pointer; font-family: 'Poppins', sans-serif; }
        .error { color: #e74c3c; font-size: 0.9rem; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Yönetim Paneli</h2>
        <?php if(isset($hata)) echo "<div class='error'>$hata</div>"; ?>
        <form method="POST">
            <input type="password" name="pass" placeholder="Şifrenizi Girin" required>
            <button type="submit" name="login" class="btn">Giriş Yap</button>
        </form>
        <button onclick="window.location.href='index.php'" class="btn-back">Ana Sayfaya Dön</button>
    </div>
</body>
</html>
<?php 
exit; 
} 

$data = json_decode(file_get_contents($jsonFile), true);
$page = isset($_GET['page']) ? $_GET['page'] : 'list';
$passSuccess = ''; $passError = '';

if (isset($_POST['change_password'])) {
    $oldPass = $_POST['old_pass']; $newPass = $_POST['new_pass']; $newPassConfirm = $_POST['new_pass_confirm'];
    if ($oldPass !== $password) { $passError = "Eski şifre hatalı!"; } 
    elseif ($newPass !== $newPassConfirm) { $passError = "Yeni şifreler uyuşmuyor!"; } 
    elseif (strlen($newPass) < 4) { $passError = "Şifre en az 4 karakter olmalıdır!"; } 
    else { $config['password'] = $newPass; file_put_contents($configFile, json_encode($config)); $password = $newPass; $passSuccess = "Şifreniz güncellendi!"; }
}

if (isset($_POST['add_product'])) {
    $cat = $_POST['category']; $gorselAdi = "default.jpg";
    if (isset($_FILES['gorsel']) && $_FILES['gorsel']['error'] == 0) {
        $yeniIsim = time() . '.' . pathinfo($_FILES['gorsel']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['gorsel']['tmp_name'], 'uploads/' . $yeniIsim)) { $gorselAdi = $yeniIsim; }
    }
    $data[$cat][] = [ "id" => time(), "ad" => $_POST['ad'], "fiyat" => $_POST['fiyat'], "icerik" => $_POST['icerik'], "gorsel" => $gorselAdi ];
    file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: admin.php?page=list"); exit;
}

if (isset($_POST['edit_product'])) {
    $id = $_POST['id']; $cat = $_POST['category']; $oldCat = $_POST['old_category']; $gorselAdi = $_POST['mevcut_gorsel'];
    if (isset($_FILES['gorsel']) && $_FILES['gorsel']['error'] == 0) {
        $yeniIsim = time() . '.' . pathinfo($_FILES['gorsel']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['gorsel']['tmp_name'], 'uploads/' . $yeniIsim)) { $gorselAdi = $yeniIsim; }
    }
    $updatedItem = [ "id" => $id, "ad" => $_POST['ad'], "fiyat" => $_POST['fiyat'], "icerik" => $_POST['icerik'], "gorsel" => $gorselAdi ];
    if ($cat === $oldCat) {
        foreach ($data[$cat] as $k => $v) { if ($v['id'] == $id) { $data[$cat][$k] = $updatedItem; break; } }
    } else {
        $data[$oldCat] = array_values(array_filter($data[$oldCat], function($i) use ($id) { return $i['id'] != $id; }));
        $data[$cat][] = $updatedItem;
    }
    file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: admin.php?page=list"); exit;
}

if (isset($_GET['delete']) && isset($_GET['cat'])) {
    $cat = $_GET['cat']; $id = $_GET['delete'];
    if(isset($data[$cat])) {
        $data[$cat] = array_values(array_filter($data[$cat], function($item) use ($id) { return $item['id'] != $id; }));
        file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header("Location: admin.php?page=list"); exit;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$scriptName = dirname($_SERVER['PHP_SELF']);
$scriptName = str_replace('\\', '/', $scriptName);
if ($scriptName === '/') $scriptName = '';
$defaultUrl = $protocol . $domainName . $scriptName . '/index.php';
$generatedQr = '';
if (isset($_POST['generate_qr'])) { $generatedQr = $_POST['qr_link']; }

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli</title>
    <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/1047/1047503.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Poppins', sans-serif; background: #F5F2EB; color: #4A4036; min-height: 100vh; display: flex; }
        
        /* MASAÜSTÜ SIDEBAR (Düzeltildi) */
        .sidebar { width: 250px; background: #2C2825; color: white; position: fixed; height: 100vh; overflow-y: auto; display: flex; flex-direction: column; z-index: 1000; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        .sidebar h2 { text-align: center; padding: 25px 20px; margin: 0; border-bottom: 1px solid #3c3733; color: #D48C46; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #d1cbc7; padding: 18px 25px; text-decoration: none; transition: 0.3s; border-left: 4px solid transparent; font-size: 0.95rem; }
        .sidebar a:hover, .sidebar a.active { background: #3c3733; color: white; border-left: 4px solid #D48C46; }
        .sidebar-bottom { margin-top: auto; border-top: 1px solid #3c3733; }
        
        .main-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(74, 64, 54, 0.05); width: 100%; margin: 0 auto; overflow: hidden; }
        h3 { margin-top: 0; border-bottom: 2px solid #F5F2EB; padding-bottom: 10px; color: #D48C46; }
        
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 30px; min-width: 500px; }
        th, td { text-align: left; padding: 15px; border-bottom: 1px solid #F5F2EB; vertical-align: middle; word-wrap: break-word; }
        th { color: #888; font-weight: 500; }
        .thumb { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; background: #eee; }
        
        .btn-action { padding: 8px 15px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; transition: 0.3s; display: inline-block; margin: 2px; color: white; border: none; cursor: pointer; text-align: center; }
        .btn-edit { background: #3498db; }
        .btn-del { background: #e74c3c; }
        .badge { background: #D48C46; color: white; padding: 3px 10px; border-radius: 20px; font-size: 0.8rem; margin-left: 10px; vertical-align: middle; }
        
        label { font-weight: 500; font-size: 0.9rem; color: #666; display: block; margin-top: 15px; }
        input, select, textarea { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 10px; font-family: 'Poppins', sans-serif; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: #D48C46; }
        .btn-save { background: #D48C46; color: white; border: none; padding: 12px 25px; margin-top: 20px; cursor: pointer; border-radius: 10px; font-weight: 600; font-size: 1rem; width: 100%; transition: 0.3s; }
        .btn-save:hover { background: #b87739; }

        .alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #dc3545; }

        /* MOBİL UYUMLULUK KORUMASI (Düzeltildi) */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { position: relative; width: 100%; height: auto; min-height: auto; flex-direction: row; flex-wrap: wrap; justify-content: space-around; padding-bottom: 0; }
            .sidebar h2 { width: 100%; padding: 15px; border-bottom: none; font-size: 1.5rem; }
            .sidebar a { flex-direction: column; gap: 5px; padding: 10px; border-left: none; border-bottom: 3px solid transparent; font-size: 0.8rem; text-align: center; flex: 1 1 auto; justify-content: center; }
            .sidebar a:hover, .sidebar a.active { border-bottom: 3px solid #D48C46; background: transparent; color: #D48C46; }
            .sidebar-bottom { display: flex; width: 100%; flex-wrap: nowrap; border-top: 1px solid #ddd; }
            .main-content { margin-left: 0; padding: 15px; width: 100%; }
            .card { padding: 20px 15px; }
            td, th { padding: 10px 8px; font-size: 0.9rem; }
            .btn-action { padding: 6px 10px; font-size: 0.8rem; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Tatlı Mola</h2>
    <a href="?page=list" class="<?php echo $page == 'list' ? 'active' : ''; ?>"><span>📦</span> Ürün Listesi</a>
    <a href="?page=add" class="<?php echo $page == 'add' ? 'active' : ''; ?>"><span>➕</span> Yeni Ekle</a>
    <a href="?page=qr" class="<?php echo $page == 'qr' ? 'active' : ''; ?>"><span>📱</span> QR Kod</a>
    <a href="?page=settings" class="<?php echo $page == 'settings' ? 'active' : ''; ?>"><span>⚙️</span> Şifre Ayarları</a>
    
    <div class="sidebar-bottom">
        <a href="index.php" style="color: #a8a8a8;"><span>🏠</span> Ana Sayfa</a>
        <a href="?logout=1" style="color: #e74c3c;"><span>🚪</span> Çıkış Yap</a>
    </div>
</div>

<div class="main-content">
    <?php if ($page == 'qr') { ?>
        <div class="card" style="max-width: 500px; text-align: center;">
            <h3>QR Kod Oluşturucu</h3>
            <p style="font-size: 0.9rem; color: #888; text-align: left;">
                Müşterilerinizin dijital menünüze ulaşması için masa barkodunuzu buradan oluşturabilirsiniz. Sistem mevcut bağlantınızı otomatik algılamaya çalıştı, gerekirse değiştirebilirsiniz.
            </p>
            <form method="POST">
                <label style="text-align: left;">Menü (Ana Sayfa) Bağlantınız:</label>
                <input type="url" name="qr_link" value="<?php echo htmlspecialchars(isset($_POST['qr_link']) ? $_POST['qr_link'] : $defaultUrl); ?>" required>
                <button type="submit" name="generate_qr" class="btn-save" style="margin-top: 15px;">Masa QR Kodunu Oluştur</button>
            </form>
            <?php if ($generatedQr): ?>
                <div style="margin-top: 35px; padding-top: 20px; border-top: 1px solid #eee;">
                    <p style="font-weight: 600; color: #D48C46; margin-bottom: 15px;">QR Kodunuz Hazır!</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($generatedQr); ?>&margin=10" alt="Menü QR Kod" style="border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 200px; height: 200px;">
                    <p style="font-size: 0.85rem; color: #888; margin-top: 15px;">
                        Görsele sağ tıklayıp veya telefonda basılı tutup <strong>"Resmi Kaydet"</strong> diyerek indirebilir ve masalarınız için bastırabilirsiniz.
                    </p>
                </div>
            <?php endif; ?>
        </div>

    <?php } elseif ($page == 'settings') { ?>
        <div class="card" style="max-width: 500px;">
            <h3>Şifre İşlemleri</h3>
            <?php if ($passSuccess) echo "<div class='alert-success'>$passSuccess</div>"; ?>
            <?php if ($passError) echo "<div class='alert-error'>$passError</div>"; ?>
            <form method="POST">
                <label>Eski Şifreniz</label><input type="password" name="old_pass" required>
                <label>Yeni Şifre</label><input type="password" name="new_pass" required>
                <label>Yeni Şifre (Tekrar)</label><input type="password" name="new_pass_confirm" required>
                <button type="submit" name="change_password" class="btn-save">Şifreyi Güncelle</button>
            </form>
        </div>

    <?php } elseif ($page == 'add') { ?>
        <div class="card" style="max-width: 600px;">
            <h3>Yeni Ürün Ekle</h3>
            <form method="POST" enctype="multipart/form-data">
                <label>Kategori</label>
                <select name="category"><option value="icecekler">İçecek</option><option value="tatlilar">Tatlı</option></select>
                <label>Ürün Adı</label><input type="text" name="ad" required>
                <label>Fiyat (TL)</label><input type="number" name="fiyat" required>
                <label>Ürün İçeriği</label><textarea name="icerik" rows="3"></textarea>
                <label>Ürün Görseli</label><input type="file" name="gorsel" accept="image/*">
                <button type="submit" name="add_product" class="btn-save">Ürünü Kaydet</button>
            </form>
        </div>

    <?php } elseif ($page == 'edit' && isset($_GET['id']) && isset($_GET['cat'])) { 
        $editId = $_GET['id']; $editCat = $_GET['cat']; $editItem = null;
        if(isset($data[$editCat])) { foreach($data[$editCat] as $item) { if($item['id'] == $editId) { $editItem = $item; break; } } }
        if($editItem) {
    ?>
        <div class="card" style="max-width: 600px;">
            <h3>Ürün Düzenle</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                <input type="hidden" name="old_category" value="<?php echo $editCat; ?>">
                <input type="hidden" name="mevcut_gorsel" value="<?php echo $editItem['gorsel']; ?>">
                
                <label>Kategori</label>
                <select name="category">
                    <option value="icecekler" <?php echo $editCat == 'icecekler' ? 'selected' : ''; ?>>İçecek</option>
                    <option value="tatlilar" <?php echo $editCat == 'tatlilar' ? 'selected' : ''; ?>>Tatlı</option>
                </select>
                <label>Ürün Adı</label><input type="text" name="ad" value="<?php echo htmlspecialchars($editItem['ad']); ?>" required>
                <label>Fiyat (TL)</label><input type="number" name="fiyat" value="<?php echo htmlspecialchars($editItem['fiyat']); ?>" required>
                <label>Ürün İçeriği</label><textarea name="icerik" rows="3"><?php echo htmlspecialchars($editItem['icerik']); ?></textarea>
                <label>Yeni Görsel</label><input type="file" name="gorsel" accept="image/*">
                <button type="submit" name="edit_product" class="btn-save">Değişiklikleri Kaydet</button>
            </form>
        </div>
        <?php } } else { ?>
        
        <div class="card" style="max-width: 900px;">
            <h3>Mevcut Ürünler</h3>
            <?php foreach (['icecekler', 'tatlilar'] as $catName) { ?>
                <h4 style="color:#666; text-transform:uppercase; font-size:0.9rem; margin-top:20px;">
                    <?php echo $catName == 'icecekler' ? 'İçecekler' : 'Tatlılar'; ?>
                    <span class="badge"><?php echo count($data[$catName]); ?></span>
                </h4>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="60">Görsel</th>
                                <th>Ürün Adı</th>
                                <th>Fiyat</th>
                                <th width="140">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($data[$catName])) { ?>
                                <?php foreach ($data[$catName] as $item) { 
                                    $gorselYolu = 'uploads/' . $item['gorsel'];
                                    $imgSrc = (isset($item['gorsel']) && $item['gorsel'] != 'default.jpg' && file_exists($gorselYolu)) ? $gorselYolu : 'https://via.placeholder.com/50x50/EAE6DF/D48C46?text=Foto';
                                ?>
                                <tr>
                                    <td><img src="<?php echo $imgSrc; ?>" class="thumb" alt="görsel"></td>
                                    <td>
                                        <strong><?php echo isset($item['ad']) ? htmlspecialchars($item['ad']) : 'İsimsiz'; ?></strong><br>
                                        <small style="color:#888;"><?php echo isset($item['icerik']) ? htmlspecialchars($item['icerik']) : ''; ?></small>
                                    </td>
                                    <td style="color:#D48C46; font-weight:600;"><?php echo isset($item['fiyat']) ? htmlspecialchars($item['fiyat']) : '0'; ?> TL</td>
                                    <td>
                                        <a href="?page=edit&id=<?php echo $item['id']; ?>&cat=<?php echo $catName; ?>" class="btn-action btn-edit">Düzenle</a>
                                        <a href="?delete=<?php echo $item['id']; ?>&cat=<?php echo $catName; ?>" class="btn-action btn-del" onclick="return confirm('Silinsin mi?')">Sil</a>
                                    </td>
                                </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr><td colspan="4" style="text-align:center; color:#999;">Bu kategoride henüz ürün yok.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

</body>
</html>