<?php
require_once '../config/database.php';
require_once '../config/common.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$uid = $_SESSION['user_id'];
$kategoriQuery = $conn->query("SELECT id, nama_kategori FROM kategori");

$kategoriList = $kategoriQuery->fetch_all(MYSQLI_ASSOC);
$filterKategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$sortOrder = isset($_GET['sort']) ? $_GET['sort'] : 'DESC';
$query = "
    SELECT t.id, t.tanggal, t.deskripsi, t.jumlah, t.tipe, k.nama_kategori
    FROM transaksi t
    LEFT JOIN kategori k ON t.kategori_id = k.id
    WHERE t.user_id = ?
";

if (!empty($filterKategori)) {
    $query .= " AND k.id = ?";
}

$query .= " ORDER BY t.tanggal $sortOrder, k.nama_kategori ASC";
$stmt = $conn->prepare($query);

if (!empty($filterKategori)) {
    $stmt->bind_param("ii", $uid, $filterKategori);
} else {
    $stmt->bind_param("i", $uid);
}

$stmt->execute();
$result = $stmt->get_result();
$totalPemasukan = 0;
$totalPengeluaran = 0;
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
    if ($row['tipe'] === 'Pemasukan') {
        $totalPemasukan += $row['jumlah'];
    } else {
        $totalPengeluaran += $row['jumlah'];
    }
}
$saldoAkhir = $totalPemasukan - $totalPengeluaran;
$totalTransaksi = count($transactions);
$result->data_seek(0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetKu | Riwayat</title>
    
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

    function formatRupiah(num){
        return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(num);
    }

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
    </script>
</head>

<body class="bg-ungu-gelap min-h-screen text-white font-sans">
<div id="messageBox" class="hidden fixed top-4 right-4 p-4 rounded-lg shadow-xl text-white z-50 transition-all duration-300">Pesan</div>

<header class="bg-ungu-gelap shadow-xl sticky top-0 z-10">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center border-b border-ungu-terang/30">
    <div class="text-2xl font-bold text-tosca-terang">BudgetKu | Riwayat</div>
    <a href="logout.php" class="text-red-400 hover:text-red-300 font-semibold">Keluar</a>
  </div>
  <nav class="max-w-7xl mx-auto px-4 py-2 flex space-x-2 text-sm font-medium">
    <a href="dashboard.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Dashboard</a>
    <a href="transaksi.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Transaksi</a>
    <a href="riwayat.php" class="px-3 py-2 rounded-lg bg-ungu-terang text-white">Riwayat</a>
    <a href="kategori.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Pengaturan Kategori</a>
    <a href="target.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Target Keuangan</a>
  </nav>
</header>

<main class="max-w-7xl mx-auto w-full p-6">
  <h1 class="text-3xl font-extrabold mb-8 border-b-2 border-tosca-terang pb-2">Riwayat Transaksi</h1>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-green-600/20 p-6 rounded-xl border border-green-400/30">
      <div class="text-green-300 text-sm font-semibold mb-2">TOTAL PEMASUKAN</div>
      <div class="text-2xl font-bold text-green-400">+ Rp <?= number_format($totalPemasukan, 0, ',', '.') ?></div>
    </div>
    <div class="bg-red-600/20 p-6 rounded-xl border border-red-400/30">
      <div class="text-red-300 text-sm font-semibold mb-2">TOTAL PENGELUARAN</div>
      <div class="text-2xl font-bold text-red-400">- Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></div>
    </div>
    <div class="bg-ungu-terang/20 p-6 rounded-xl border border-ungu-terang/30">
      <div class="text-tosca-terang text-sm font-semibold mb-2">SALDO AKHIR</div>
      <div class="text-2xl font-bold <?= $saldoAkhir >= 0 ? 'text-tosca-terang' : 'text-red-400' ?>">
        <?= $saldoAkhir >= 0 ? '+' : '' ?> Rp <?= number_format(abs($saldoAkhir), 0, ',', '.') ?>
      </div>
    </div>
  </div>

  <!-- Filter Section -->
  <div class="bg-ungu-gelap p-6 rounded-xl shadow-lg border border-ungu-terang/50 mb-8">
    <h2 class="text-xl font-bold mb-4 text-tosca-terang">Filter & Sortir</h2>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm mb-2 font-semibold text-white">Filter Kategori:</label>
        <select name="kategori" class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white focus:border-tosca-terang focus:outline-none transition">
          <option value="">Semua Kategori</option>
          <?php foreach ($kategoriList as $k): ?>
            <option value="<?= $k['id'] ?>" <?= ($filterKategori == $k['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($k['nama_kategori']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-2 font-semibold text-white">Urutkan Berdasarkan:</label>
        <select name="sort" class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white focus:border-tosca-terang focus:outline-none transition">
          <option value="DESC" <?= $sortOrder == 'DESC' ? 'selected' : '' ?>>Terbaru</option>
          <option value="ASC" <?= $sortOrder == 'ASC' ? 'selected' : '' ?>>Terlama</option>
        </select>
      </div>
      <div class="flex items-end">
        <button type="submit" class="w-full py-3 bg-tosca-terang hover:bg-tosca-terang/80 text-ungu-gelap font-bold rounded-lg transition duration-300">
          Terapkan Filter
        </button>
      </div>
    </form>
  </div>

  <!-- Transactions Table -->
  <div class="bg-ungu-gelap p-6 rounded-xl shadow-lg border border-ungu-terang/50">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-tosca-terang">Daftar Transaksi</h2>
      <div class="text-sm text-white/70">
        Total: <span class="font-semibold"><?= $totalTransaksi ?> transaksi</span>
      </div>
    </div>

    <?php if ($totalTransaksi > 0): ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="text-white/70 border-b border-ungu-terang/30">
            <tr>
              <th class="p-3 text-left">Tanggal</th>
              <th class="p-3 text-left">Deskripsi</th>
              <th class="p-3 text-right">Jumlah</th>
              <th class="p-3 text-left">Tipe</th>
              <th class="p-3 text-left">Kategori</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transactions as $row): ?>
              <tr class="border-t border-ungu-terang/20 hover:bg-ungu-terang/10 transition">
                <td class="p-3"><?= htmlspecialchars($row['tanggal']) ?></td>
                <td class="p-3 font-medium"><?= htmlspecialchars($row['deskripsi']) ?></td>
                <td class="p-3 text-right font-semibold <?= $row['tipe'] === 'Pemasukan' ? 'text-green-400' : 'text-red-400' ?>">
                  <?= $row['tipe'] === 'Pemasukan' ? '+' : '-' ?> Rp <?= number_format($row['jumlah'], 0, ',', '.') ?>
                </td>
                <td class="p-3">
                  <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $row['tipe'] === 'Pemasukan' ? 'bg-green-400/20 text-green-300' : 'bg-red-400/20 text-red-300' ?>">
                    <?= ucfirst(htmlspecialchars($row['tipe'])) ?>
                  </span>
                </td>
                <td class="p-3 text-white/80">
                  <?= $row['tipe'] === 'Pengeluaran' ? htmlspecialchars($row['nama_kategori'] ?? '-') : '<span class="text-white/40">-</span>' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center py-12">
        <div class="text-6xl mb-4 text-ungu-terang/50">📊</div>
        <h3 class="text-xl font-semibold text-white/80 mb-2">Belum ada data transaksi</h3>
        <p class="text-white/60 mb-6">Mulai dengan menambahkan transaksi pertama Anda</p>
        <a href="transaksi.php" class="inline-block bg-tosca-terang hover:bg-tosca-terang/80 text-ungu-gelap font-bold py-3 px-6 rounded-lg transition duration-300">
          Tambah Transaksi
        </a>
      </div>
    <?php endif; ?>
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