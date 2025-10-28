<?php
require_once '../config/database.php';
require_once '../config/common.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$uid = $_SESSION['user_id'];

$income = 0; $expense = 0;
$stmt = $conn->prepare("SELECT tipe, SUM(jumlah) as total FROM transaksi WHERE user_id = ? GROUP BY tipe");
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    if ($r['tipe'] === 'Pemasukan') $income = $r['total'];
    else $expense = $r['total'];
}
$balance = $income - $expense;

$allocations = [];
$stmt2 = $conn->prepare("SELECT k.nama_kategori, SUM(t.jumlah) as total 
                         FROM transaksi t 
                         JOIN kategori k ON t.kategori_id = k.id 
                         WHERE t.user_id = ? AND t.tipe = 'Pengeluaran'
                         GROUP BY k.nama_kategori");
$stmt2->bind_param('i', $uid);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($r = $res2->fetch_assoc()) {
    $allocations[$r['nama_kategori']] = $r['total'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BudgetKu | Dashboard</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                'ungu-gelap': '#17072b',
                'tosca-terang': '#22ddd2',
                'biru-terang': '#2e73ea',
                'ungu-terang': '#8c15e9'
            },
            fontFamily: {
                sans: ['Poppins','sans-serif'],
                serif: ['Zilla Slab','serif']
            }
        }
    }
};

function alertUser(text, type='info') {
    const mb = document.getElementById('messageBox');
    mb.textContent = text;
    mb.classList.remove('hidden','bg-green-600','bg-red-600','bg-blue-600');
    let color='bg-blue-600';
    if(type==='success') color='bg-green-600';
    else if(type==='error') color='bg-red-600';
    mb.classList.add(color);
    setTimeout(()=> mb.classList.add('hidden'), 4000);
}

window.onload = () => {
    const allocations = <?=json_encode($allocations)?>;
    renderPieChart(allocations);
}

function formatRupiah(num){
    return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(num);
}

function renderPieChart(allocations){
    const ctx = document.getElementById('transactionPieChart');
    if(!ctx) return;
    const total = Object.values(allocations).reduce((a,b)=>a+b,0);
    new Chart(ctx,{
        type:'doughnut',
        data:{
            labels:Object.keys(allocations),
            datasets:[{
                label:'Pengeluaran',
                data:Object.values(allocations),
                backgroundColor:['#8c15e9','#22ddd2','#2e73ea','#ffb703','#fb8500'],
                hoverOffset:4
            }]
        },
        options:{
            plugins:{
                legend:{ labels:{ color:'white', font:{ family:'Poppins' } } },
                tooltip:{
                    callbacks:{
                        label:(ctx)=>{
                            const v=ctx.parsed;
                            const p=total>0?((v/total)*100).toFixed(1):0;
                            return `${ctx.label}: ${formatRupiah(v)} (${p}%)`;
                        }
                    }
                }
            }
        }
    });
}
</script>
</head>

<body class="bg-ungu-gelap min-h-screen text-white font-sans">
<div id="messageBox" class="hidden fixed top-4 right-4 p-4 rounded-lg shadow-xl text-white z-50 transition-all duration-300">Pesan</div>

<header class="bg-ungu-gelap shadow-xl sticky top-0 z-10">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center border-b border-ungu-terang/30">
    <div class="text-2xl font-bold text-tosca-terang">BudgetKu | Dashboard</div>
    <a href="logout.php" class="text-red-400 hover:text-red-300 font-semibold">Keluar</a>
  </div>
  <nav class="max-w-7xl mx-auto px-4 py-2 flex space-x-2 text-sm font-medium">
    <a href="dashboard.php" class="px-3 py-2 rounded-lg <?=basename($_SERVER['PHP_SELF'])=='dashboard.php'?'bg-ungu-terang text-white':'hover:bg-ungu-terang/50'?>">Dashboard</a>
    <a href="transaksi.php" class="px-3 py-2 rounded-lg <?=basename($_SERVER['PHP_SELF'])=='transaksi.php'?'bg-ungu-terang text-white':'hover:bg-ungu-terang/50'?>">Transaksi</a>
    <a href="riwayat.php" class="px-3 py-2 rounded-lg <?=basename($_SERVER['PHP_SELF'])=='riwayat.php'?'bg-ungu-terang text-white':'hover:bg-ungu-terang/50'?>">Riwayat</a>
    <a href="kategori.php" class="px-3 py-2 rounded-lg <?=basename($_SERVER['PHP_SELF'])=='kategori.php'?'bg-ungu-terang text-white':'hover:bg-ungu-terang/50'?>">Pengaturan Kategori</a>
    <a href="target.php" class="px-3 py-2 rounded-lg <?=basename($_SERVER['PHP_SELF'])=='target.php'?'bg-ungu-terang text-white':'hover:bg-ungu-terang/50'?>">Target Keuangan</a>
  </nav>
</header>

<main class="max-w-7xl mx-auto w-full p-6">
  <h1 class="text-3xl font-extrabold mb-8 border-b-2 border-tosca-terang pb-2">Dashboard Finansial</h1>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
    <div class="bg-ungu-terang p-6 rounded-xl shadow-lg">
      <h2 class="text-xl font-bold mb-4 text-white">Laporan Tabungan</h2>
      <div class="text-4xl font-extrabold text-tosca-terang mb-4"><?= 'Rp '.number_format($balance,0,',','.') ?></div>
      <div class="flex justify-between text-sm text-white/80">
        <div>
          <p class="font-semibold text-green-300">Pemasukan:</p>
          <p><?= 'Rp '.number_format($income,0,',','.') ?></p>
        </div>
        <div>
          <p class="font-semibold text-red-300">Pengeluaran:</p>
          <p><?= 'Rp '.number_format($expense,0,',','.') ?></p>
        </div>
      </div>
      <a href="riwayat.php" class="block mt-4 text-center font-bold text-sm bg-ungu-gelap hover:bg-ungu-gelap/80 text-tosca-terang py-2 rounded-lg transition">Lihat Selengkapnya (Riwayat)</a>
    </div>

    <div class="bg-ungu-gelap p-6 rounded-xl shadow-lg border border-ungu-terang/50">
      <h2 class="text-xl font-bold mb-4 text-white">Info Capaian Target</h2>
      <p class="text-white/70 mb-2">Target: <span class="font-semibold"><?= 'Rp '.number_format(5000000,0,',','.') ?></span></p>
      <?php
        $targetAmount = 5000000;
        $progress = min($balance,$targetAmount);
        $targetProgress = min(($progress / $targetAmount)*100,100);
        $targetRemaining = max($targetAmount - $balance,0);
      ?>
      <div class="w-full bg-ungu-terang/50 rounded-full h-3">
        <div class="h-3 rounded-full transition-all duration-1000" 
             style="width: <?=$targetProgress?>%; background-color: <?=$targetProgress>=100?"#22ddd2":"#2e73ea"?>;"></div>
      </div>
      <p class="text-sm mt-2 text-white/60">Progres: <?=number_format($targetProgress,1)?>% | Sisa: <?= 'Rp '.number_format($targetRemaining,0,',','.') ?></p>
      <a href="target.php" class="block mt-4 text-center font-bold text-sm bg-biru-terang hover:bg-biru-terang/80 text-white py-2 rounded-lg transition">Atur Target Keuangan</a>
    </div>

    <div class="bg-ungu-gelap p-6 rounded-xl shadow-lg border border-ungu-terang/50">
      <h2 class="text-xl font-bold mb-4 text-white">Alokasi Pengeluaran Utama</h2>
      <div class="w-full max-w-sm mx-auto">
        <canvas id="transactionPieChart"></canvas>
      </div>
      <p class="text-center text-sm text-white/60 mt-4">* Berdasarkan data kategori pengeluaran.</p>
    </div>
  </div>
</main>

<footer class="bg-ungu-gelap/50 p-8 border-t border-ungu-terang/20 mt-12">
  <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 text-white/70">
    <div>
      <h3 class="text-lg font-bold mb-3 text-tosca-terang font-serif">Tentang BudgetKu</h3>
      <p class="text-sm">BudgetKu dirancang untuk transparansi finansial pribadi.</p>
      <p class="text-xs mt-3">&copy; 2025 BudgetKu. Hak Cipta Dilindungi.</p>
    </div>
    <div>
      <h3 class="text-lg font-bold mb-3 text-ungu-terang">Aplikasi</h3>
      <ul class="space-y-2 text-sm">
        <li><a href="dashboard.php" class="hover:text-tosca-terang">Dashboard</a></li>
        <li><a href="transaksi.php" class="hover:text-tosca-terang">Input Transaksi</a></li>
        <li><a href="riwayat.php" class="hover:text-tosca-terang">Riwayat Keuangan</a></li>
        <li><a href="target.php" class="hover:text-tosca-terang">Target Tabungan</a></li>
      </ul>
    </div>
    <div>
      <h3 class="text-lg font-bold mb-3 text-ungu-terang">Tips & Edukasi</h3>
      <ul class="space-y-2 text-sm">
        <li><a href="#" class="hover:text-biru-terang">Tips Mengatur Gaji</a></li>
        <li><a href="#" class="hover:text-biru-terang">Panduan Kategori</a></li>
        <li><a href="#" class="hover:text-biru-terang">Tujuan Keuangan</a></li>
      </ul>
    </div>
    <div>
      <h3 class="text-lg font-bold mb-3 text-ungu-terang">Kontak Kami</h3>
      <ul class="space-y-2 text-sm">
        <li>✉ support@budgetku.com</li>
        <li>📱 +62 812 XXXX XXXX</li>
        <li class="mt-4"><a href="https://instagram.com/iqbalramadhanreal" target="_blank" class="text-biru-terang hover:text-tosca-terang">Instagram Owner</a></li>
      </ul>
    </div>
  </div>
</footer>

</body>
</html>
