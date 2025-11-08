<?php
error_reporting(0);
require_once '../config/db.php';
require_once '../config/parsedown-master/Parsedown.php';          // ✅ include markdown parser

session_start();

$Parsedown = new Parsedown();             //create a Parsedown instance
$Parsedown->setSafeMode(true);            //enable safe mode (security: prevents HTML injection)

$loggedIn = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;

// Fetch user's profile picture (for navbar)

if ($loggedIn && !isset($_SESSION['profile_pic'])) {
  $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($user && $user['profile_pic']) {
    $_SESSION['profile_pic'] = $user['profile_pic'];
  }
}

// 🔍 Handle search input
$search = trim($_GET['search'] ?? '');

// Fetch blog posts with optional search
if ($search) {
  $sql = "SELECT bp.*, u.username 
          FROM blogPost bp 
          JOIN users u ON bp.user_id = u.id 
          WHERE bp.title LIKE ? OR bp.content LIKE ? OR u.username LIKE ?
          ORDER BY bp.created_at DESC";
  $stmt = $conn->prepare($sql);
  $like = "%$search%";
  $stmt->execute([$like, $like, $like]);
} else {
  $stmt = $conn->prepare("SELECT bp.*, u.username 
                          FROM blogPost bp 
                          JOIN users u ON bp.user_id = u.id 
                          ORDER BY bp.created_at DESC");
  $stmt->execute();
}
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch first image for each post
$images = $conn->query("SELECT post_id, MIN(imgid) AS first_image_id FROM blog_images GROUP BY post_id")->fetchAll(PDO::FETCH_KEY_PAIR);
$firstImages = [];
if (!empty($images)) {
  $ids = implode(',', array_values($images));
  $imgStmt = $conn->query("SELECT imgid, post_id, image_path FROM blog_images WHERE imgid IN ($ids)");
  foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $firstImages[$row['post_id']] = $row['image_path'];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Blogs | Blog App</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

  <style>
  body {
    font-family: 'Poppins', sans-serif;
    position: relative;
    min-height: 100vh;
    color: #f5f3ff;
    overflow-x: hidden;
    background: linear-gradient(135deg, #1a102d, #60516cff);
  }

  body::before {
    content: "";
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background:
      linear-gradient(135deg, rgba(26,16,45,0.8), rgba(59,7,100,0.8)),
      url('../assets/background.jpg') center/cover no-repeat;
    filter: blur(1px);
    z-index: -1;
  }

  .glass-card {
    background: rgba(49, 25, 94, 0.8);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 1rem;
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .glass-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(234,71,152,0.5);
  }

  .fade-in { animation: fadeIn 0.7s ease forwards; }
  @keyframes fadeIn { from{opacity:0;transform:translateY(10px);} to{opacity:1;transform:translateY(0);} }

  .btn-primary {
    background-color: #a21caf;
    color: white;
    font-weight: 600;
    padding: 0.5rem 1.5rem;
    border-radius: 0.5rem;
    transition: 0.3s;
  }
  .btn-primary:hover {
    background-color: #db2777;
    transform: scale(1.05);
  }

  input[type="text"] {
    background-color: rgba(255,255,255,0.1);
    border: 1px solid rgba(216,180,254,0.3);
    color: #f5f3ff;
    padding: 0.6rem 1rem;
    border-radius: 0.5rem;
    width: 100%;
  }
  input[type="text"]:focus {
    outline: none;
    border-color: #f9a8d4;
    box-shadow: 0 0 10px #f9a8d4;
  }

  </style>
</head>

<body class="fade-in">

<!-- Navbar -->
<nav class="bg-[#2e1065]/70 backdrop-blur-md border-b border-purple-700 shadow-lg sticky top-0 z-10">
  <div class="max-w-6xl mx-auto flex justify-between items-center px-4 py-3">
    
    <!-- Create Blog Button -->
    <a href="create.php" class="bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold px-5 py-2 rounded-lg shadow-md hover:from-purple-500 hover:to-pink-500 hover:shadow-pink-400/40 transition-all duration-300 flex items-center gap-2">
      ➕ Create Blog
    </a>
    
    <h1 class="text-3xl text- font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-pink-400 via-purple-400 to-pink-400 tracking-wide animate-pulse ">
      The Blog Gallery
    </h1>

    <!-- Profile Picture -->
    <a href="profile.php" title="Profile" class="relative group">
      <?php 
        $profilePicPath = isset($_SESSION['profile_pic']) && !empty($_SESSION['profile_pic']) 
          ? '../' . htmlspecialchars($_SESSION['profile_pic']) 
          : '../uploads/default-avatar.png';
      ?>
      <img src="<?= $profilePicPath ?>" 
           alt="Profile"
           onerror="this.src='../uploads/default-avatar.png';"
           class="w-10 h-10 rounded-full border-2 border-pink-400 object-cover shadow-md transition-all duration-300 group-hover:shadow-pink-500/70 group-hover:scale-110">
    </a>
  </div>
</nav>

<!-- 🔍 Search Bar -->
<div class="max-w-3xl mx-auto px-4 mt-6">
  <form method="GET" class="flex gap-2">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search blogs by title, content, or author...">
    <button class="btn-primary">Search</button>
  </form>
</div>

<!-- Blog list -->
<main class="max-w-6xl mx-auto px-4 py-8 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
  <?php if (empty($posts)): ?>
    <p class="col-span-full text-center text-pink-200 text-lg mt-20">No blogs found<?= $search ? " for “$search”" : "" ?>.</p>
  <?php endif; ?>

  <?php foreach ($posts as $post): ?>
    <?php
      $imagePath = $firstImages[$post['bid']] ?? $post['image_path'] ?? 'uploads/default-blog.webp';
    ?>
    <div class="glass-card overflow-hidden p-4 fade-in">
      <img src="../<?= htmlspecialchars($imagePath) ?>" class="h-48 w-full object-cover rounded-lg mb-3 shadow-md" alt="cover">

      <div>
        <h2 class="text-lg font-semibold text-pink-300 mb-1"><?= htmlspecialchars($post['title']) ?></h2>
        <p class="text-pink-200 text-sm mb-2">
          By <span class="font-medium text-pink-400"><?= htmlspecialchars($post['username']) ?></span> • 
          <?= date('F j, Y', strtotime($post['created_at'])) ?>
        </p>
        <p class="text-white text-sm mb-4 leading-snug">
          <?= substr(strip_tags($Parsedown->text($post['content'])), 0, 100) ?>...
        </p>
        <a href="single.php?id=<?= $post['bid']; ?>"
           class="text-pink-400 text-sm font-semibold hover:text-pink-300 transition">
           Read More 👉
        </a>
      </div>
    </div>
  <?php endforeach; ?>
</main>

</body>
</html>
