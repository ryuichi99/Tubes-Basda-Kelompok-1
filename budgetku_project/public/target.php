<?php
require_once '../config/database.php';
require_once '../config/common.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$uid = $_SESSION['user_id'];

// Tambah Target
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nama = trim($_POST['nama']);
    $jumlah = (int)$_POST['jumlah'];
    if ($nama !== '' && $jumlah > 0) {
        $stmt = $conn->prepare("INSERT INTO target (user_id, nama_target, jumlah_target) VALUES (?, ?, ?)");
        $stmt->bind_param('isi', $uid, $nama, $jumlah);
        $stmt->execute();
        header("Location: target.php?success=1");
        exit;
    } else {
        header("Location: target.php?error=1");
        exit;
    }
}

// Hapus Target
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM target WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    header("Location: target.php?deleted=1");
    exit;
}

$target = $conn->query("SELECT * FROM target WHERE user_id = $uid ORDER BY created_at DESC LIMIT 1")->fetch_assoc();

$saldo = 0;
$qSaldo = $conn->prepare("SELECT 
    SUM(CASE WHEN tipe='pemasukan' THEN jumlah ELSE -jumlah END) AS total 
    FROM transaksi WHERE user_id=?");
$qSaldo->bind_param('i', $uid);
$qSaldo->execute();
$res = $qSaldo->get_result()->fetch_assoc();
$saldo = (int)($res['total'] ?? 0);

$progress = 0;
$sisa = 0;
if ($target) {
    $progress = min(($saldo / $target['jumlah_target']) * 100, 100);
    $sisa = max($target['jumlah_target'] - $saldo, 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BudgetKu | Target</title>
<script src="https://cdn.tailwindcss.com"></script>
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
  const box = document.getElementById('messageBox');
  box.textContent = text;
  box.classList.remove('hidden','bg-green-600','bg-red-600','bg-blue-600');
  let color='bg-blue-600';
  if(type==='success') color='bg-green-600';
  if(type==='error') color='bg-red-600';
  box.classList.add(color);
  setTimeout(()=>{ box.classList.add('hidden'); },4000);
}
window.onload = () => {
  const params = new URLSearchParams(window.location.search);
  if(params.has('success')) alertUser('Target berhasil disimpan!','success');
  if(params.has('error')) alertUser('Nama atau jumlah target tidak valid.','error');
  if(params.has('deleted')) alertUser('Target berhasil dihapus.','info');
};
</script>
</head>
<body class="bg-ungu-gelap min-h-screen text-white font-sans">

<div id="messageBox" class="hidden fixed top-4 right-4 p-4 rounded-lg shadow-xl text-white z-50 transition-all duration-300">Pesan</div>

<header class="bg-ungu-gelap shadow-xl sticky top-0 z-10">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center border-b border-ungu-terang/30">
    <div class="text-2xl font-bold text-tosca-terang">BudgetKu | Target</div>
    <a href="logout.php" class="text-red-400 hover:text-red-300 font-semibold">Keluar</a>
  </div>
  <nav class="max-w-7xl mx-auto px-4 py-2 overflow-x-auto">
    <div class="flex space-x-2 text-sm font-medium">
      <a href="dashboard.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Dashboard</a>
      <a href="transaksi.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Transaksi</a>
      <a href="riwayat.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Riwayat</a>
      <a href="kategori.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Kategori</a>
      <span class="px-3 py-2 rounded-lg bg-ungu-terang text-white">Target Keuangan</span>
    </div>
  </nav>
</header>

<main class="max-w-7xl mx-auto w-full p-4 sm:p-6 lg:p-8">
  <h1 class="text-3xl font-extrabold mb-8 text-white border-b-2 border-tosca-terang pb-2">Target Tabungan Keuangan</h1>
  
  <div class="mb-8 p-6 bg-ungu-gelap rounded-xl border border-ungu-terang/50">
    <h2 class="text-xl font-bold mb-4 text-tosca-terang">Status Target Saat Ini</h2>
    <?php if ($target): ?>
      <p class="text-white/70 text-lg mb-2">Tujuan: <span class="font-bold text-tosca-terang"><?=esc($target['nama_target'])?></span></p>
      <p class="text-white/70 mb-4">Jumlah Target: <span class="font-bold"><?=number_format($target['jumlah_target'],0,',','.')?></span></p>
      <div class="mb-4">
        <p class="text-white/70 mb-1">Progres Capaian (<?=number_format($progress,1)?>%)</p>
        <div class="w-full bg-ungu-terang/50 rounded-full h-4">
          <div class="h-4 rounded-full transition-all duration-1000"
               style="width: <?=$progress?>%; background-color: <?=$progress>=100 ? '#22ddd2' : '#2e73ea'?>;">
          </div>
        </div>
        <p class="text-sm mt-1 text-white/60">Saldo Saat Ini: Rp<?=number_format($saldo,0,',','.')?> | Sisa Dibutuhkan: Rp<?=number_format($sisa,0,',','.')?></p>
      </div>
      <a href="target.php?delete=<?=$target['id']?>" onclick="return confirm('Hapus target ini?')" class="mt-4 text-red-400 hover:text-red-300 text-sm">Hapus Target Ini</a>
    <?php else: ?>
      <p class="text-white/70 text-center py-4">Anda belum menetapkan target tabungan. Tentukan impian finansial Anda!</p>
    <?php endif; ?>
  </div>

  <!-- Form Target -->
  <div class="p-6 bg-ungu-gelap rounded-xl border border-tosca-terang/50">
    <h2 class="text-xl font-bold mb-4 text-white">Atur Target Baru</h2>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="mb-4">
        <label class="block text-sm font-medium text-white/70 mb-1">Nama Target</label>
        <input type="text" name="nama" required value="<?= $target['nama_target'] ?? '' ?>"
          class="w-full p-2 rounded-lg bg-ungu-gelap border border-ungu-terang text-white placeholder-white/50 focus:ring-biru-terang focus:border-biru-terang">
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium text-white/70 mb-1">Jumlah Target (IDR)</label>
        <input type="number" name="jumlah" required value="<?= $target['jumlah_target'] ?? '' ?>"
          class="w-full p-2 rounded-lg bg-ungu-gelap border border-ungu-terang text-white placeholder-white/50 focus:ring-biru-terang focus:border-biru-terang">
      </div>
      <button type="submit" class="w-full py-2 bg-tosca-terang hover:bg-tosca-terang/80 text-ungu-gelap font-bold rounded-lg transition duration-300">
        Simpan Target
      </button>
    </form>
  </div>
</main>
</body>
</html>
