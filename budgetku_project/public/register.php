<?php
require_once '../config/database.php';
require_once '../config/common.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (strlen($username) < 3) $errors[] = 'Username minimal 3 karakter';
    if (strlen($password) < 4) $errors[] = 'Password minimal 4 karakter';
    if ($password !== $confirm) $errors[] = 'Konfirmasi password tidak cocok';
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
        $stmt->bind_param('ss', $username, $hash);
        if ($stmt->execute()) {
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Gagal menyimpan user: ' . $conn->error;
        }
    }
}
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar - BudgetKu</title>
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
  <div class="w-full max-w-3xl mx-auto p-6">
    <div class="p-8 bg-ungu-gelap/50 border border-ungu-terang/30 rounded-xl shadow-xl">
      <h1 class="text-3xl font-extrabold mb-4 text-biru-terang">Daftar BudgetKu</h1>
      <?php if ($errors): ?>
        <div class="bg-red-600 p-3 rounded mb-4">
          <?php foreach($errors as $e) echo '<div>'.esc($e).'</div>'; ?>
        </div>
      <?php endif; ?>
      <form method="post" class="space-y-4">
        <input name="username" required placeholder="Username" class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white">
        <input type="password" name="password" required placeholder="Password" class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white">
        <input type="password" name="confirm" required placeholder="Konfirmasi Password" class="w-full p-3 rounded-lg bg-ungu-gelap border border-ungu-terang text-white">
        <button class="w-full py-3 bg-ungu-terang hover:bg-ungu-terang/80 text-white font-extrabold rounded-lg">Buat Akun</button>
      </form>
      <p class="mt-4 text-sm text-white/60">Sudah punya akun? <a href="index.php" class="text-tosca-terang">Masuk</a></p>
    </div>
  </div>
</body>
</html>
