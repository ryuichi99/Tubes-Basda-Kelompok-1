<?php
require_once '../config/database.php';
require_once '../config/common.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$uid = $_SESSION['user_id'];

// ==== CREATE ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    $tanggal = $_POST['tanggal'];
    $deskripsi = $_POST['deskripsi'];
    $jumlah = (int)$_POST['jumlah'];
    $tipe = $_POST['tipe'];
    $kategori = ($tipe === 'Pengeluaran' && isset($_POST['kategori'])) ? $_POST['kategori'] : null;

    $stmt = $conn->prepare("INSERT INTO transaksi (user_id, tanggal, deskripsi, jumlah, tipe, kategori_id) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('issisi', $uid, $tanggal, $deskripsi, $jumlah, $tipe, $kategori);
    $stmt->execute();

    $_SESSION['alert'] = ['text' => 'Transaksi berhasil ditambahkan!', 'type' => 'success'];
    header('Location: transaksi.php');
    exit;
}

// ==== DELETE ====
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM transaksi WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $_SESSION['alert'] = ['text' => 'Transaksi berhasil dihapus.', 'type' => 'error'];
    header('Location: transaksi.php');
    exit;
}

// ==== UPDATE ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $tanggal = $_POST['tanggal'];
    $deskripsi = $_POST['deskripsi'];
    $jumlah = (int)$_POST['jumlah'];
    $tipe = $_POST['tipe'];
    $kategori = ($tipe === 'Pengeluaran' && isset($_POST['kategori'])) ? $_POST['kategori'] : null;

    $stmt = $conn->prepare("UPDATE transaksi SET tanggal=?, deskripsi=?, jumlah=?, tipe=?, kategori_id=? WHERE id=? AND user_id=?");
    $stmt->bind_param('ssisiii', $tanggal, $deskripsi, $jumlah, $tipe, $kategori, $id, $uid);
    $stmt->execute();

    $_SESSION['alert'] = ['text' => 'Transaksi berhasil diperbarui!', 'type' => 'success'];
    header('Location: transaksi.php');
    exit;
}

// Jika user klik "Edit"
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM transaksi WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
}

// ==== READ ====
$transactions = $conn->query("SELECT t.*, k.nama_kategori FROM transaksi t 
    LEFT JOIN kategori k ON t.kategori_id = k.id 
    WHERE t.user_id = $uid ORDER BY t.tanggal DESC");
$categories = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");

// ==== Alert ====
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BudgetKu | Transaksi</title>

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
    const mb = document.getElementById('messageBox');
    mb.textContent = text;
    mb.classList.remove('hidden','bg-green-600','bg-red-600','bg-blue-600');
    let color='bg-blue-600';
    if(type==='success') color='bg-green-600';
    else if(type==='error') color='bg-red-600';
    mb.classList.add(color);
    setTimeout(()=> mb.classList.add('hidden'), 4000);
}

function switchTab(tabType) {
    const tabPemasukan = document.getElementById('tabPemasukan');
    const tabPengeluaran = document.getElementById('tabPengeluaran');
    const expenseCategoryGroup = document.getElementById('expenseCategoryGroup');
    const tipeInput = document.querySelector('input[name="tipe"]');
    const deskripsiInput = document.querySelector('input[name="deskripsi"]');

    if (tabType === 'Pemasukan') {
        tabPemasukan.classList.add('bg-ungu-terang', 'text-white');
        tabPemasukan.classList.remove('text-white/70');
        tabPengeluaran.classList.remove('bg-ungu-terang', 'text-white');
        tabPengeluaran.classList.add('text-white/70');
        tipeInput.value = 'Pemasukan';
        if (expenseCategoryGroup) expenseCategoryGroup.style.display = 'none';
        if (deskripsiInput) deskripsiInput.placeholder = 'Gaji Bulanan, Bonus Proyek, Investasi, dll.';
    } else {
        tabPengeluaran.classList.add('bg-ungu-terang', 'text-white');
        tabPengeluaran.classList.remove('text-white/70');
        tabPemasukan.classList.remove('bg-ungu-terang', 'text-white');
        tabPemasukan.classList.add('text-white/70');
        tipeInput.value = 'Pengeluaran';
        if (expenseCategoryGroup) expenseCategoryGroup.style.display = 'block';
        if (deskripsiInput) deskripsiInput.placeholder = 'Makan Siang, Belanja, Transportasi, Listrik, dll.';
    }
}

window.onload = () => {
    <?php if ($alert): ?>
    alertUser("<?=$alert['text']?>", "<?=$alert['type']?>");
    <?php endif; ?>

    // Set initial tab state for new transactions
    <?php if (!$editData): ?>
    switchTab('Pemasukan');
    <?php endif; ?>
}
</script>
</head>

<body class="bg-ungu-gelap min-h-screen text-white font-sans">
<div id="messageBox" class="hidden fixed top-4 right-4 p-4 rounded-lg shadow-xl text-white z-50 transition-all duration-300">Pesan</div>

<header class="bg-ungu-gelap shadow-xl sticky top-0 z-10">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center border-b border-ungu-terang/30">
    <div class="text-2xl font-bold text-tosca-terang">BudgetKu | Transaksi</div>
    <a href="logout.php" class="text-red-400 hover:text-red-300 font-semibold">Keluar</a>
  </div>
  <nav class="max-w-7xl mx-auto px-4 py-2 flex space-x-2 text-sm font-medium">
    <a href="dashboard.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Dashboard</a>
    <a href="transaksi.php" class="px-3 py-2 rounded-lg bg-ungu-terang text-white">Transaksi</a>
    <a href="riwayat.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Riwayat</a>
    <a href="kategori.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Pengaturan Kategori</a>
    <a href="target.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Target Keuangan</a>
  </nav>
</header>

<main class="max-w-7xl mx-auto w-full p-6">
  <h1 class="text-3xl font-extrabold mb-8 border-b-2 border-tosca-terang pb-2">
    <?= $editData ? 'Edit Transaksi' : 'Input Transaksi Keuangan' ?>
  </h1>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Form Section -->
    <div class="bg-ungu-gelap p-6 rounded-xl shadow-lg border border-ungu-terang/50">
      <!-- Tab -->
      <?php if (!$editData): ?>
      <div class="flex mb-6 border-b border-ungu-terang/50">
        <button id="tabPemasukan" type="button" onclick="switchTab('Pemasukan')" class="flex-1 px-6 py-3 font-semibold rounded-tl-lg transition duration-300">Pemasukan</button>
        <button id="tabPengeluaran" type="button" onclick="switchTab('Pengeluaran')" class="flex-1 px-6 py-3 font-semibold rounded-tr-lg transition duration-300">Pengeluaran</button>
      </div>
      <?php endif; ?>

      <!-- Form -->
      <form method="post" class="space-y-4">
        <input type="hidden" name="action" value="<?= $editData ? 'edit' : 'add' ?>">
        <?php if ($editData): ?>
          <input type="hidden" name="id" value="<?=$editData['id']?>">
        <?php endif; ?>
        <input type="hidden" name="tipe" value="<?= $editData ? $editData['tipe'] : 'Pemasukan' ?>">
        
        <div>
          <label class="block text-sm mb-2 font-semibold text-tosca-terang">Tanggal</label>
          <input type="date" name="tanggal" required value="<?= $editData ? $editData['tanggal'] : date('Y-m-d') ?>" 
                 class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white focus:border-tosca-terang focus:outline-none transition">
        </div>
        
        <div>
          <label class="block text-sm mb-2 font-semibold text-tosca-terang">Deskripsi</label>
          <input type="text" name="deskripsi" required value="<?= $editData ? esc($editData['deskripsi']) : '' ?>" 
                 placeholder="<?= $editData ? ($editData['tipe'] === 'Pemasukan' ? 'Gaji Bulanan, Bonus Proyek, dll.' : 'Makan Siang, Belanja, Transportasi, dll.') : 'Gaji Bulanan, Bonus Proyek, dll.' ?>" 
                 class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white focus:border-tosca-terang focus:outline-none transition">
        </div>
        
        <?php if (!$editData || $editData['tipe'] === 'Pengeluaran'): ?>
        <div id="expenseCategoryGroup" style="<?= (!$editData || $editData['tipe'] === 'Pengeluaran') ? '' : 'display: none;' ?>">
          <label class="block text-sm mb-2 font-semibold text-tosca-terang">Kategori Pengeluaran</label>
          <select name="kategori" class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white focus:border-tosca-terang focus:outline-none transition">
            <option value="" disabled <?= !$editData ? 'selected' : '' ?>>Pilih Kategori</option>
            <?php $categories2 = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC"); ?>
            <?php while($c = $categories2->fetch_assoc()): ?>
              <option value="<?=$c['id']?>" <?= $editData && $editData['kategori_id'] == $c['id'] ? 'selected' : '' ?>>
                <?=$c['nama_kategori']?>
              </option>
            <?php endwhile; ?>
          </select>
          <p class="text-xs text-white/60 mt-1">* Kategori hanya untuk transaksi pengeluaran</p>
        </div>
        <?php endif; ?>
        
        <div>
          <label class="block text-sm mb-2 font-semibold text-tosca-terang">Jumlah (IDR)</label>
          <input type="number" name="jumlah" required value="<?= $editData ? $editData['jumlah'] : '' ?>" 
                 placeholder="5000000" 
                 class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white focus:border-tosca-terang focus:outline-none transition">
        </div>
        
        <button type="submit" class="w-full py-3 <?= $editData ? 'bg-yellow-400 hover:bg-yellow-500 text-ungu-gelap' : 'bg-tosca-terang hover:bg-tosca-terang/80 text-ungu-gelap' ?> font-bold rounded-lg transition duration-300">
          <?= $editData ? 'Perbarui Transaksi' : 'Simpan Transaksi' ?>
        </button>
        
        <?php if ($editData): ?>
        <a href="transaksi.php" class="block text-center py-2 bg-ungu-terang hover:bg-ungu-terang/80 text-white font-bold rounded-lg transition duration-300">
          Batal Edit
        </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-ungu-gelap p-6 rounded-xl shadow-lg border border-ungu-terang/50">
      <h2 class="text-2xl font-bold mb-6 text-tosca-terang border-b border-tosca-terang/30 pb-2">Transaksi Terbaru</h2>
      <div class="space-y-3 max-h-96 overflow-y-auto">
        <?php $counter = 0; ?>
        <?php foreach($transactions as $t): ?>
          <?php if ($counter++ >= 10) break; ?>
          <div class="p-3 rounded-lg border border-ungu-terang/30 bg-ungu-gelap/50 hover:bg-ungu-terang/10 transition">
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="font-semibold text-white"><?=esc($t['deskripsi'])?></div>
                <div class="text-sm text-white/70">
                  <?=esc($t['tanggal'])?> 
                  <?php if ($t['tipe'] === 'Pengeluaran' && $t['nama_kategori']): ?>
                    • <?=esc($t['nama_kategori'])?>
                  <?php endif; ?>
                </div>
              </div>
              <div class="text-right">
                <div class="font-bold <?= $t['tipe'] === 'Pemasukan' ? 'text-green-400' : 'text-red-400' ?>">
                  <?= $t['tipe'] === 'Pemasukan' ? '+' : '-' ?> Rp <?=number_format($t['jumlah'],0,',','.')?>
                </div>
                <div class="text-xs mt-1">
                  <span class="px-2 py-1 rounded-full <?= $t['tipe'] === 'Pemasukan' ? 'bg-green-400/20 text-green-300' : 'bg-red-400/20 text-red-300' ?>">
                    <?=esc($t['tipe'])?>
                  </span>
                </div>
                <div class="text-xs text-white/60 mt-1">
                  <a href="transaksi.php?edit=<?=$t['id']?>" class="text-yellow-400 hover:text-yellow-300 mr-2">Edit</a>
                  <a href="transaksi.php?delete=<?=$t['id']?>" onclick="return confirm('Yakin ingin menghapus transaksi ini?')" class="text-red-400 hover:text-red-300">Hapus</a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if ($counter === 0): ?>
          <div class="text-center py-8 text-white/60">
            <p>Belum ada transaksi</p>
            <p class="text-sm mt-2">Mulai dengan menambahkan transaksi pertama Anda</p>
          </div>
        <?php endif; ?>
      </div>
      <a href="riwayat.php" class="block mt-4 text-center font-bold text-sm bg-biru-terang hover:bg-biru-terang/80 text-white py-2 rounded-lg transition">
        Lihat Semua Transaksi
      </a>
    </div>
  </div>

  <!-- Full Transactions Table -->
  <div class="bg-ungu-gelap p-6 rounded-xl shadow-lg border border-ungu-terang/50 mt-8">
    <h2 class="text-2xl font-bold mb-6 text-tosca-terang border-b border-tosca-terang/30 pb-2">Daftar Semua Transaksi</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="text-white/70 border-b border-ungu-terang/30">
          <tr>
            <th class="p-3 text-left">Tanggal</th>
            <th class="p-3 text-left">Deskripsi</th>
            <th class="p-3 text-right">Jumlah</th>
            <th class="p-3 text-left">Tipe</th>
            <th class="p-3 text-left">Kategori</th>
            <th class="p-3 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($transactions as $t): ?>
            <tr class="border-t border-ungu-terang/20 hover:bg-ungu-terang/10 transition">
              <td class="p-3"><?=esc($t['tanggal'])?></td>
              <td class="p-3 font-medium"><?=esc($t['deskripsi'])?></td>
              <td class="p-3 text-right font-semibold <?= $t['tipe'] === 'Pemasukan' ? 'text-green-400' : 'text-red-400' ?>">
                <?= $t['tipe'] === 'Pemasukan' ? '+' : '-' ?> Rp <?=number_format($t['jumlah'],0,',','.')?>
              </td>
              <td class="p-3">
                <span class="px-2 py-1 rounded-full text-xs <?= $t['tipe'] === 'Pemasukan' ? 'bg-green-400/20 text-green-300' : 'bg-red-400/20 text-red-300' ?>">
                  <?=esc($t['tipe'])?>
                </span>
              </td>
              <td class="p-3 text-white/80">
                <?php if ($t['tipe'] === 'Pengeluaran'): ?>
                  <?=esc($t['nama_kategori'] ?? '-')?>
                <?php else: ?>
                  <span class="text-white/40">-</span>
                <?php endif; ?>
              </td>
              <td class="p-3 text-center">
                <a href="transaksi.php?edit=<?=$t['id']?>" class="text-yellow-400 hover:text-yellow-300 mr-3 transition">Edit</a>
                <a href="transaksi.php?delete=<?=$t['id']?>" onclick="return confirm('Yakin ingin menghapus transaksi ini?')" class="text-red-400 hover:text-red-300 transition">Hapus</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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