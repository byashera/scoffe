<?php
// Hataları önlemek için dosya kontrolü
$jsonFile = 'menu.json';
if (!file_exists($jsonFile)) {
    // Dosya yoksa, boş şablonla oluştur (Hataları engeller)
    $defaultData = ["icecekler" => [], "tatlilar" => []];
    file_put_contents($jsonFile, json_encode($defaultData));
}

$jsonData = file_get_contents($jsonFile);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tatlı Mola Menü</title>
    <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/1047/1047503.png" type="image/png">
    <link rel="stylesheet" href="style.css"> </head>
<body>

<header>
    <h1 id="brand-logo">Tatlı Mola</h1>
    <p style="color: #888; font-size: 0.9rem;">Menümüze Hoş Geldiniz</p>
</header>

<div class="tabs">
    <button class="tab-btn active" onclick="showCategory('icecekler', this)">İçecekler</button>
    <button class="tab-btn" onclick="showCategory('tatlilar', this)">Tatlılar</button>
</div>

<div class="menu-container" id="menu-display">
    </div>

<script>
    const menuData = <?php echo $jsonData ?: '{}'; ?>;

    function showCategory(category, btnElement) {
        const display = document.getElementById('menu-display');
        display.innerHTML = '';

        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');

        if (!menuData[category] || menuData[category].length === 0) {
            display.innerHTML = '<p style="text-align:center; color:#888;">Bu kategoride henüz ürün yok.</p>';
            return;
        }

        menuData[category].forEach((item, index) => {
            // Animasyonların sırayla gelmesi için her karta ufak bir gecikme (delay) ekliyoruz
            const delay = index * 0.1;
            
            // Görsel yoksa veya admin eklememişse varsayılan bir görsel atıyoruz
            const imgSrc = (item.gorsel && item.gorsel !== "default.jpg") 
                           ? "uploads/" + item.gorsel 
                           : "https://via.placeholder.com/75x75/EAE6DF/D48C46?text=Mola";

            const card = `
                <div class="product-card" style="animation-delay: ${delay}s">
                    <img src="${imgSrc}" alt="${item.ad}" class="product-image">
                    <div class="product-info">
                        <h3>${item.ad}</h3>
                        <p>${item.icerik}</p>
                    </div>
                    <div class="product-price">${item.fiyat} TL</div>
                </div>
            `;
            display.innerHTML += card;
        });
    }

    // Sayfa yüklendiğinde ilk kategoriyi aç
    window.onload = () => {
        showCategory('icecekler', document.querySelector('.tab-btn.active'));
    };

    // --- GİZLİ ADMİN GİRİŞ SİSTEMİ ---
    // Başlığa (Logoya) ardışık 5 kez tıklanırsa admin.php'ye yönlendir
    let clickCount = 0;
    let clickTimer;
    
    document.getElementById('brand-logo').addEventListener('click', () => {
        clickCount++;
        clearTimeout(clickTimer);
        
        if (clickCount >= 5) {
            window.location.href = 'admin.php'; 
        } else {
            // Eğer tıklamalar arası 1 saniyeden fazla sürerse sayacı sıfırla
            clickTimer = setTimeout(() => {
                clickCount = 0; 
            }, 1000);
        }
    });
</script>
</body>
</html>