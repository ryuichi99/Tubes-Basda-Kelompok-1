<?php
require_once '../config/database.php';
session_start();
require_once '../config/common.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - BudgetKu</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
    if(!mb) return;
    mb.textContent = text;
    mb.classList.remove('hidden','bg-green-600','bg-red-600','bg-blue-600','bg-ungu-gelap');
    let color='bg-blue-600';
    if(type==='success') color='bg-green-600';
    if(type==='error') color='bg-red-600';
    mb.classList.add(color);
    mb.classList.remove('opacity-0');
    setTimeout(()=>{ mb.classList.add('opacity-0'); }, 3000);
  }
</script>
</head>

<body class="bg-ungu-gelap min-h-screen text-white font-sans flex items-center justify-center">
<div id="messageBox" class="hidden fixed top-4 right-4 p-3 rounded-lg shadow-xl text-white z-50 transition-opacity opacity-0"></div>
  <div class="w-full max-w-4xl mx-auto p-6">
    <div class="grid md:grid-cols-2 gap-8 items-center">
      <div class="p-8 bg-ungu-gelap/50 border border-ungu-terang/30 rounded-xl shadow-xl">
        <h2 class="text-3xl font-extrabold mb-4 text-tosca-terang">Selamat Datang di BudgetKu</h2>
        <p class="text-white/70 mb-6">Masuk untuk mengelola keuanganmu dengan mudah. Gunakan akun admin contoh: <span class="font-bold">admin / 12345</span></p>
        <?php if ($error): ?>
          <div class="bg-red-600 p-3 rounded mb-4"><?=esc($error)?></div>
        <?php endif; ?>
        <form method="post" class="space-y-4">
          <input name="username" required placeholder="Username" class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white">
          <input type="password" name="password" required placeholder="Password" class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white">
          <button class="w-full py-3 bg-tosca-terang hover:bg-tosca-terang/80 text-ungu-gelap font-extrabold rounded-lg">Masuk</button>
        </form>
      </div>
      <div class="p-8 bg-ungu-gelap rounded-xl border border-ungu-terang/30 shadow-xl">
        <h3 class="text-2xl font-bold text-biru-terang mb-3">Belum punya akun?</h3>
        <p class="text-white/70 mb-6">Daftar sekarang untuk menyimpan transaksi, target, dan kategori kamu secara aman.</p>
        <a href="register.php" class="inline-block py-3 px-6 bg-ungu-terang hover:bg-ungu-terang/80 rounded-lg font-bold text-white">Daftar Akun</a>
      </div>
    </div>
  </div>
</body>
</html>
