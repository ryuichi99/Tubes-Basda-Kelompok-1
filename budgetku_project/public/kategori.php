<?php
require_once '../config/database.php';
require_once '../config/common.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

if (!isset($conn) || !$conn instanceof mysqli) {
    die("Koneksi database tidak ditemukan. Pastikan '../config/database.php' mendefinisikan \$conn (mysqli).");
}

// ==== TAMBAH KATEGORI ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $batas = (int)($_POST['batas'] ?? 0);
    $periode = trim($_POST['periode'] ?? 'Bulanan');

    if ($nama_kategori === '') {
        $_SESSION['alert'] = ['text' => 'Nama kategori tidak boleh kosong.', 'type' => 'error'];
        header('Location: kategori.php');
        exit;
    }

    // Cek apakah kategori sudah ada
    $stmt = $conn->prepare("SELECT id FROM kategori WHERE nama_kategori = ?");
    $stmt->bind_param('s', $nama_kategori);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $_SESSION['alert'] = ['text' => 'Kategori dengan nama tersebut sudah ada.', 'type' => 'error'];
        header('Location: kategori.php');
        exit;
    }

    // Insert kategori baru
    $now = date('Y-m-d H:i:s');
    $ins = $conn->prepare("INSERT INTO kategori (nama_kategori, batas, periode, created_at) VALUES (?, ?, ?, ?)");
    $ins->bind_param('siss', $nama_kategori, $batas, $periode, $now);
    
    if ($ins->execute()) {
        $_SESSION['alert'] = ['text' => 'Kategori berhasil ditambahkan!', 'type' => 'success'];
    } else {
        $_SESSION['alert'] = ['text' => 'Gagal menambahkan kategori.', 'type' => 'error'];
    }
    
    header('Location: kategori.php');
    exit;
}

// ==== UPDATE KATEGORI ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $batas = (int)($_POST['batas'] ?? 0);
    $periode = trim($_POST['periode'] ?? 'Bulanan');

    if ($nama_kategori === '') {
        $_SESSION['alert'] = ['text' => 'Nama kategori tidak boleh kosong.', 'type' => 'error'];
        header('Location: kategori.php');
        exit;
    }

    // Cek duplikasi nama kategori
    $stmt = $conn->prepare("SELECT id FROM kategori WHERE nama_kategori = ? AND id != ?");
    $stmt->bind_param('si', $nama_kategori, $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $_SESSION['alert'] = ['text' => 'Kategori dengan nama tersebut sudah ada.', 'type' => 'error'];
        header('Location: kategori.php');
        exit;
    }

    // Update data kategori
    $stmt = $conn->prepare("UPDATE kategori SET nama_kategori = ?, batas = ?, periode = ? WHERE id = ?");
    $stmt->bind_param('sisi', $nama_kategori, $batas, $periode, $id);
    
    if ($stmt->execute()) {
        $_SESSION['alert'] = ['text' => 'Kategori berhasil diperbarui!', 'type' => 'success'];
    } else {
        $_SESSION['alert'] = ['text' => 'Gagal memperbarui kategori.', 'type' => 'error'];
    }
    
    header('Location: kategori.php');
    exit;
}

// ==== DELETE KATEGORI ====
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Pastikan kategori belum dipakai di transaksi
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transaksi WHERE kategori_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $count = $res->fetch_assoc()['count'];
    
    if ($count > 0) {
        $_SESSION['alert'] = ['text' => 'Tidak dapat menghapus kategori yang sudah digunakan dalam transaksi.', 'type' => 'error'];
        header('Location: kategori.php');
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM kategori WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $_SESSION['alert'] = ['text' => 'Kategori berhasil dihapus.', 'type' => 'success'];
    } else {
        $_SESSION['alert'] = ['text' => 'Gagal menghapus kategori.', 'type' => 'error'];
    }
    
    header('Location: kategori.php');
    exit;
}

// ==== EDIT DATA ====
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM kategori WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
}

// ==== AMBIL SEMUA KATEGORI ====
$categories = [];
$stmt = $conn->prepare("SELECT id, nama_kategori, batas, periode, created_at FROM kategori ORDER BY created_at DESC");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

function formatCurrency($n) {
    return 'Rp ' . number_format((int)$n, 0, ',', '.');
}
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>BudgetKu | Kategori</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Zilla+Slab:wght@400;700&display=swap" rel="stylesheet">
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
  if(type==='error') color='bg-red-600';
  mb.classList.add(color);
  mb.style.opacity = '1';
  setTimeout(()=>{ mb.style.opacity='0'; setTimeout(()=>mb.classList.add('hidden'),400); }, 3000);
}

function confirmDelete(categoryName, categoryId) {
  if (confirm(`Yakin ingin menghapus kategori "${categoryName}"?`)) {
    window.location.href = `kategori.php?delete=${categoryId}`;
  }
}

window.onload = () => {
  <?php if ($alert): ?>
  alertUser("<?=$alert['text']?>", "<?=$alert['type']?>");
  <?php endif; ?>
}
</script>
</head>

<body class="bg-ungu-gelap min-h-screen text-white font-sans">
<div id="messageBox" class="hidden fixed top-4 right-4 p-4 rounded-lg shadow-xl text-white z-50 transition-all duration-300 opacity-0">Pesan</div>

<header class="bg-ungu-gelap shadow-xl sticky top-0 z-10">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center border-b border-ungu-terang/30">
    <div class="text-2xl font-bold text-tosca-terang">BudgetKu | Kategori</div>
    <button onclick="location.href='logout.php'" class="text-red-400 hover:text-red-300">Keluar</button>
  </div>
  <nav class="max-w-7xl mx-auto px-4 py-2">
    <div class="flex space-x-2 text-sm">
      <a href="dashboard.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Dashboard</a>
      <a href="transaksi.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Transaksi</a>
      <a href="riwayat.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Riwayat</a>
      <span class="px-3 py-2 rounded-lg bg-ungu-terang">Pengaturan Kategori</span>
      <a href="target.php" class="px-3 py-2 rounded-lg hover:bg-ungu-terang/50">Target Keuangan</a>
    </div>
  </nav>
</header>

<main class="max-w-7xl mx-auto p-6">
  <h1 class="text-3xl font-extrabold mb-2 text-white border-b-2 border-tosca-terang pb-2">
    <?= $editData ? 'Edit Kategori' : 'Kelola Kategori Pengeluaran' ?>
  </h1>
  <p class="mb-6 text-white/70">
    <?= $editData ? 'Edit detail kategori pengeluaran' : 'Tambah, edit, atau hapus kategori untuk mengatur pengeluaran Anda' ?>
  </p>

  <!-- Form Tambah/Edit Kategori -->
  <div class="bg-ungu-gelap p-6 rounded-xl shadow-lg border border-ungu-terang/50 mb-8">
    <h2 class="text-xl font-bold mb-4 text-tosca-terang">
      <?= $editData ? 'Edit Kategori' : 'Tambah Kategori Baru' ?>
    </h2>
    
    <form method="post" class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <input type="hidden" name="action" value="<?= $editData ? 'update' : 'add' ?>">
      <?php if ($editData): ?>
        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
      <?php endif; ?>
      
      <div>
        <label class="block text-sm mb-2 font-semibold text-white">Nama Kategori</label>
        <input type="text" name="nama_kategori" required 
               value="<?= $editData ? h($editData['nama_kategori']) : '' ?>" 
               placeholder="Contoh: Makanan, Transportasi, Hiburan"
               class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white focus:border-tosca-terang focus:outline-none transition">
      </div>
      
      <div>
        <label class="block text-sm mb-2 font-semibold text-white">Batas Maksimum</label>
        <input type="number" name="batas" required 
               value="<?= $editData ? $editData['batas'] : '0' ?>" 
               placeholder="0"
               class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white focus:border-tosca-terang focus:outline-none transition">
      </div>
      
      <div>
        <label class="block text-sm mb-2 font-semibold text-white">Periode</label>
        <select name="periode" class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white focus:border-tosca-terang focus:outline-none transition">
          <option value="Harian" <?= $editData && $editData['periode'] === 'Harian' ? 'selected' : '' ?>>Harian</option>
          <option value="Mingguan" <?= $editData && $editData['periode'] === 'Mingguan' ? 'selected' : '' ?>>Mingguan</option>
          <option value="Bulanan" <?= (!$editData || $editData['periode'] === 'Bulanan') ? 'selected' : '' ?>>Bulanan</option>
        </select>
      </div>
      
      <div class="flex items-end">
        <button type="submit" class="w-full py-3 bg-tosca-terang hover:bg-tosca-terang/80 text-ungu-gelap font-bold rounded-lg transition duration-300">
          <?= $editData ? 'Perbarui Kategori' : 'Tambah Kategori' ?>
        </button>
      </div>
    </form>
    
    <?php if ($editData): ?>
    <div class="mt-4">
      <a href="kategori.php" class="inline-block bg-ungu-terang hover:bg-ungu-terang/80 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
        Batal Edit
      </a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Daftar Kategori -->
  <div class="bg-ungu-gelap p-6 rounded-xl shadow-lg border border-ungu-terang/50">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-tosca-terang">Daftar Kategori Anda</h2>
      <div class="text-sm text-white/70">
        Total: <span class="font-semibold"><?= count($categories) ?> kategori</span>
      </div>
    </div>

    <?php if (count($categories) > 0): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($categories as $cat): ?>
          <div class="p-5 bg-ungu-gelap rounded-xl shadow-md border border-ungu-terang/50 hover:border-tosca-terang/50 transition duration-300">
            <div class="flex justify-between items-start mb-3">
              <h3 class="text-xl font-semibold text-tosca-terang"><?= h($cat['nama_kategori']) ?></h3>
              <div class="flex space-x-2">
                <a href="kategori.php?edit=<?= $cat['id'] ?>" class="text-yellow-400 hover:text-yellow-300 transition" title="Edit">
                  ✏️
                </a>
                <button onclick="confirmDelete('<?= h($cat['nama_kategori']) ?>', <?= $cat['id'] ?>)" 
                        class="text-red-400 hover:text-red-300 transition" title="Hapus">
                  🗑️
                </button>
              </div>
            </div>
            
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-white/70">Batas:</span>
                <span class="font-semibold text-white"><?= formatCurrency($cat['batas']) ?></span>
              </div>
              <div class="flex justify-between">
                <span class="text-white/70">Periode:</span>
                <span class="font-semibold text-white"><?= h($cat['periode']) ?></span>
              </div>
              <div class="flex justify-between">
                <span class="text-white/70">Dibuat:</span>
                <span class="text-white/60 text-xs"><?= date('d M Y', strtotime($cat['created_at'])) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-12">
        <div class="text-6xl mb-4 text-ungu-terang/50">📂</div>
        <h3 class="text-xl font-semibold text-white/80 mb-2">Belum ada kategori</h3>
        <p class="text-white/60 mb-6">Mulai dengan menambahkan kategori pertama Anda</p>
      </div>
    <?php endif; ?>
  </div>
</main>

<footer class="bg-ungu-gelap/50 p-8 border-t border-ungu-terang/20 mt-12">
  <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 text-white/70">
    <div>
      <h3 class="text-lg font-bold mb-3 text-tosca-terang font-serif">Tentang BudgetKu</h3>
      <p class="text-sm">BudgetKu dirancang untuk transparansi finansial pribadi.</p>
      <p class="text-xs mt-3">&copy; 2025 BudgetKu.</p>
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
        <li>Kelola pengeluaran rutin</li>
        <li>Buat tabungan darurat</li>
        <li>Analisis laporan keuangan</li>
      </ul>
    </div>
    <div>
      <h3 class="text-lg font-bold mb-3 text-tosca-terang">Hubungi Kami</h3>
      <ul class="space-y-2 text-sm">
        <li>Email: support@budgetku.com</li>
        <li>Telp: +62 812 3456 7890</li>
      </ul>
    </div>
  </div>
</footer>
</body>
</html>
