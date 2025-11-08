<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: ../index.php');
  exit();
}

$user_id = $_SESSION['user_id'];

// Handle delete request
if (isset($_GET['delete'])) {
  $bid = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM blogpost WHERE bid = ? AND user_id = ?");
  $stmt->execute([$bid, $user_id]);
  $_SESSION['success'] = "Blog deleted successfully!";
  header("Location: profile.php");
  exit();
}

// Handle profile picture update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
  $targetDir = "../uploads/";
  if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

  $fileName = basename($_FILES["profile_pic"]["name"]);
  $uniqueName = time() . "_" . $fileName;
  $targetFile = $targetDir . $uniqueName;
  $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
  $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

  if (in_array($fileType, $allowedTypes)) {
    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {
      $relativePath = "uploads/" . $uniqueName;
      $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
      $stmt->execute([$relativePath, $user_id]);
      $_SESSION['profile_pic'] = $relativePath;
      $_SESSION['success'] = "Profile picture updated!";
    }
  } else {
    $_SESSION['error'] = "Invalid file type. Only images are allowed.";
  }
  

  header("Location: profile.php");
  exit();
}

// Handle bio or username update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
  $username = trim($_POST['username']);
  $bio = trim($_POST['bio']);
  $stmt = $conn->prepare("UPDATE users SET username=?, bio=? WHERE id=?");
  $stmt->execute([$username, $bio, $user_id]);
  $_SESSION['success'] = "Profile updated!";
  header("Location: profile.php");
  exit();
}

// Fetch user info
$stmt = $conn->prepare("SELECT id, username, email, profile_pic, bio, created_at FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user stats
$postStmt = $conn->prepare("SELECT COUNT(*) AS totalBlogs FROM blogpost WHERE user_id=?");
$postStmt->execute([$user_id]);
$totalBlogs = $postStmt->fetch(PDO::FETCH_ASSOC)['totalBlogs'];

$likesStmt = $conn->prepare("
  SELECT COUNT(*) AS totalLikes 
  FROM likes l 
  JOIN blogpost b ON l.post_id = b.bid 
  WHERE b.user_id = ?
");
$likesStmt->execute([$user_id]);
$totalLikes = $likesStmt->fetch(PDO::FETCH_ASSOC)['totalLikes'];

// Fetch all user posts
$posts = $conn->prepare("SELECT bid, title, created_at FROM blogpost WHERE user_id=? ORDER BY created_at DESC");
$posts->execute([$user_id]);
$user_posts = $posts->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($user['username']) ?>'s Profile | D_Blogger</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      color: #f5f3ff;
      min-height: 100vh;
      position: relative;
      overflow-x: hidden;
      background: linear-gradient(135deg, #1a102d, #3b0764);
    }

    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background:
        linear-gradient(135deg, rgba(26, 16, 45, 0.88), rgba(59, 7, 100, 0.88)),
        url('../assets/background.jpg') center/cover no-repeat;
      filter: blur(0px);
      z-index: -1;
    }
    .glow { box-shadow: 0 0 15px rgba(168, 85, 247, 0.6); }
    input, textarea { color: white; }
  </style>
</head>
<body class="min-h-screen flex flex-col">

  <!-- Navbar -->
  <nav class="bg-[#2e1065]/70 backdrop-blur-md border-b border-purple-700 shadow-lg sticky top-0 z-10">
    <div class="max-w-6xl mx-auto flex justify-between items-center px-4 py-3">
    <a href="blogs.php" 
       class="bg-pink-600 border border-purple-500/40 text-purple-300 px-4 py-2 rounded-lg hover:bg-pink-700 transition shadow-md">
       👈 Back to Blogs
    </a>
    <div class="flex items-center gap-3">
      <span class="text-gray-300">Welcome, <?= htmlspecialchars($user['username']) ?>!</span>
      <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-sm transition">Logout</a>
    </div>
  </nav>

  <!-- Profile Section -->
  <main class="max-w-4xl mx-auto mt-10 bg-[#2e1065]/70 backdrop-blur-md rounded-2xl shadow-xl p-8">
    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-8">

      <!-- Profile Picture with Edit Icon -->
      <div class="relative group">
        <img src="../<?= $user['profile_pic'] ?: 'uploads/default-avatar.png' ?>"
             alt="Profile"
             class="w-36 h-36 rounded-full object-cover border-4 border-purple-500 glow mx-auto">
        <form method="POST" enctype="multipart/form-data" class="absolute inset-0 flex justify-center items-center opacity-0 group-hover:opacity-100 transition">
          <label for="profile_pic" class="cursor-pointer bg-black/50 rounded-full p-3 hover:bg-black/70 transition">
            <i data-lucide="pen-line" class="text-white w-6 h-6"></i>
          </label>
          <input type="file" id="profile_pic" name="profile_pic" accept="image/*" class="hidden" onchange="this.form.submit()">
        </form>
      </div>

      <!-- User Info -->
      <div class="flex-1">
        <h2 class="text-3xl font-bold mb-2 text-purple-300"><?= htmlspecialchars($user['username']) ?></h2>
        <p class="text-gray-400 mb-1">📧 <?= htmlspecialchars($user['email']) ?></p>
        <p class="text-gray-500 text-sm mb-6">Joined: <?= date('F j, Y', strtotime($user['created_at'])) ?></p>

        <div class="flex gap-10 mb-8 text-center">
          <div class="bg-[#3b0764]/60 px-4 py-3 rounded-lg border border-purple-500/40 glow">
            <p class="text-2xl font-bold text-purple-300"><?= $totalBlogs ?></p>
            <p class="text-gray-400 text-sm">Blogs Posted</p>
          </div>
          <div class="bg-[#3b0764]/60 px-4 py-3 rounded-lg border border-purple-500/40 glow">
            <p class="text-2xl font-bold text-pink-300"><?= $totalLikes ?></p>
            <p class="text-gray-400 text-sm">Total Likes</p>
          </div>
        </div>

        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                   class="w-full px-4 py-2 rounded-lg bg-[#3b0764]/60 border border-purple-400 focus:ring-2 focus:ring-purple-500 outline-none">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Bio</label>
            <textarea name="bio" rows="3"
                      class="w-full px-4 py-2 rounded-lg bg-[#3b0764]/60 border border-purple-400 focus:ring-2 focus:ring-purple-500 outline-none"
                      placeholder="Write something about yourself..."><?= htmlspecialchars($user['bio']) ?></textarea>
          </div>
          <button type="submit" name="update_info"
                  class="bg-pink-600 hover:bg-pink-700 px-6 py-2 rounded-lg font-semibold glow transition-transform hover:scale-105">
            Save Changes
          </button>
        </form>
      </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($_SESSION['success'])): ?>
      <p class="mt-6 text-green-400 font-medium bg-green-900/30 p-2 rounded"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
    <?php elseif (!empty($_SESSION['error'])): ?>
      <p class="mt-6 text-red-400 font-medium bg-red-900/30 p-2 rounded"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <hr class="my-10 border-purple-600/40">

    <!-- User’s Blogs -->
    <section>
      <h3 class="text-2xl font-semibold mb-4 text-purple-300">📝 Your Blog Posts</h3>
      <?php if (empty($user_posts)): ?>
        <p class="text-gray-400">You haven’t published any blogs yet.</p>
      <?php else: ?>
        <ul class="space-y-3">
          <?php foreach ($user_posts as $p): ?>
            <li class="bg-[#3b0764]/50 border border-purple-500/30 rounded-lg p-4 flex justify-between items-center hover:bg-[#4c1d95]/60 transition">
              <div>
                <a href="single.php?id=<?= $p['bid'] ?>" class="text-purple-300 font-medium hover:underline">
                  <?= htmlspecialchars($p['title']) ?>
                </a>
                <p class="text-gray-400 text-xs"><?= date('F j, Y', strtotime($p['created_at'])) ?></p>
              </div>
              <div class="flex gap-3">
                <a href="edit.php?id=<?= $p['bid'] ?>" class="text-blue-400 hover:text-blue-500">
                  <i data-lucide="edit-3" class="w-5 h-5"></i>
                </a>
                <a href="?delete=<?= $p['bid'] ?>" onclick="return confirm('Are you sure you want to delete this post?')" class="text-red-400 hover:text-red-500">
                  <i data-lucide="trash-2" class="w-5 h-5"></i>
                </a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </main>

  <!-- Footer --> 
    <footer class="text-center text-gray-400 text-sm py-6 mt-10">
      &copy; <?= date('Y') ?> BlogHaven . All Rights Reserved.
    </footer>

  <script>lucide.createIcons();</script>
</body>
</html>
