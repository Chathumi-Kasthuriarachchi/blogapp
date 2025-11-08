<?php
require_once '../config/db.php';
require_once '../config/parsedown-master/Parsedown.php';          // ✅ include markdown parser

session_start();

$Parsedown = new Parsedown();             //create a Parsedown instance
$Parsedown->setSafeMode(true);            //enable safe mode (security: prevents HTML injection)

if (!isset($_GET['id'])) die("Post not found.");

$bid = intval($_GET['id']);
$loggedIn = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;

// 🔹 Fetch post with author
$stmt = $conn->prepare("
  SELECT bp.*, u.username 
  FROM blogpost bp
  JOIN users u ON bp.user_id = u.id
  WHERE bp.bid = ?
");
$stmt->execute([$bid]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) die("Post not found.");

// 🔹 Fetch all images for this post (for gallery)
$imgStmt = $conn->prepare("SELECT image_path FROM blog_images WHERE post_id = ?");
$imgStmt->execute([$bid]);
$images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

// 🔹 Handle new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
  if (!$loggedIn) die("You must log in to comment.");
  $comment = trim($_POST['comment_text']);
  if ($comment !== '') {
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$bid, $user_id, $comment]);
    header("Location: single.php?id=$bid");
    exit();
  }
}

// 🔹 Handle likes
if (isset($_POST['like_post']) && $loggedIn) {
  $check = $conn->prepare("SELECT * FROM likes WHERE post_id=? AND user_id=?");
  $check->execute([$bid, $user_id]);
  if ($check->rowCount() > 0) {
    $conn->prepare("DELETE FROM likes WHERE post_id=? AND user_id=?")->execute([$bid, $user_id]);
  } else {
    $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)")->execute([$bid, $user_id]);
  }
  header("Location: single.php?id=$bid");
  exit();
}

// 🔹 Fetch comments
$comments = $conn->prepare("
  SELECT c.*, u.username FROM comments c 
  JOIN users u ON c.user_id = u.id 
  WHERE c.post_id = ? ORDER BY c.created_at DESC
");
$comments->execute([$bid]);
$all_comments = $comments->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Fetch like count
$countStmt = $conn->prepare("SELECT COUNT(*) AS totalLikes FROM likes WHERE post_id=?");
$countStmt->execute([$bid]);
$totalLikes = $countStmt->fetch(PDO::FETCH_ASSOC)['totalLikes'];

// 🔹 Check if current user liked
$userLiked = false;
if ($loggedIn) {
  $chk = $conn->prepare("SELECT 1 FROM likes WHERE post_id=? AND user_id=?");
  $chk->execute([$bid, $user_id]);
  $userLiked = $chk->rowCount() > 0;
}

// 🔹 Get user profile image (for navbar)
$profilePic = '../uploads/default-avatar.png';
if ($loggedIn && isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id=?");
  $stmt->execute([$_SESSION['user_id']]);
  $pic = $stmt->fetchColumn();
  if ($pic) $profilePic = "../" . htmlspecialchars($pic);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($post['title']) ?> | Blog App</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

  <style>
     body {
      font-family: 'Poppins', sans-serif;
      position: relative;
      min-height: 100vh;
      color: #f5f3ff;
      overflow-x: hidden;
      background: linear-gradient(135deg, #1a102d, #3b0764);
    }

    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: 
        linear-gradient(135deg, rgba(26, 16, 45, 0.85), rgba(59, 7, 100, 0.85)),
        url('../assets/background.jpg') center/cover no-repeat;
      filter: blur(0px);
      z-index: -1;
    }

    .glass-card {
      background: rgba(46, 16, 101, 0.85);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(168, 85, 247, 0.3);
      border-radius: 1rem;
      padding: 2rem;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
    }

    .gallery {
      display: flex;
      gap: 1rem;
      overflow-x: auto;
      scroll-behavior: smooth;
      padding-bottom: 10px;
    }
    .gallery img {
      height: 250px;
      width: auto;
      border-radius: 12px;
      border: 2px solid rgba(236, 72, 153, 0.4);
      transition: transform 0.3s;
    }
    .gallery img:hover {
      transform: scale(1.05);
      border-color: #f472b6;
    }

    .comment-box {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(216, 180, 254, 0.2);
      border-radius: 0.5rem;
      padding: 0.75rem;
      margin-top: 0.75rem;
      transition: 0.3s;
    }
    .comment-box:hover {
      border-color: #f9a8d4;
      box-shadow: 0 0 8px rgba(236, 72, 153, 0.3);
    }

    input, textarea {
      background-color: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(216, 180, 254, 0.3);
      color: #f5f3ff;
      width: 100%;
      border-radius: 8px;
      padding: 0.5rem;
    }

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

    .fade-in {
      animation: fadeIn 0.7s ease forwards;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

     /* Lightbox popup styles */
    .lightbox {
      display: none;
      position: fixed;
      z-index: 50;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.85);
      justify-content: center;
      align-items: center;
    }
    .lightbox img {
      max-width: 90%;
      max-height: 90%;
      border-radius: 12px;
      box-shadow: 0 0 25px rgba(255, 192, 203, 0.6);
    }
  </style>
</head>

<body class="fade-in">

  <!-- Navbar -->
<nav class="bg-[#2e1065]/70 backdrop-blur-md border-b border-purple-700 shadow-lg sticky top-0 z-10">
  <div class="max-w-6xl mx-auto flex justify-between items-center px-4 py-3">
    <a href="blogs.php" 
       class="bg-pink-600 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-pink-700 transition shadow-md">
       👈 Back to Blogs
    </a>
    <a href="profile.php">
      <img src="<?= $profilePic ?>" alt="Profile" class="w-10 h-10 rounded-full border-2 border-pink-400 object-cover hover:scale-105 transition">
    </a>
  </nav>

  <!-- Blog Post -->
  <main class="max-w-4xl mx-auto glass-card mt-10">
  
    <h1 class="text-3xl font-bold mb-2 text-pink-300"><?= htmlspecialchars($post['title']) ?></h1>
    <p class="text-pink-200 text-sm mb-6">
      By <span class="font-semibold text-pink-400"><?= htmlspecialchars($post['username']) ?></span> • <?= date('F j, Y', strtotime($post['created_at'])) ?>
    </p>   
  
  <!-- ✅ Normal Gallery (No Scroll, Click to View) -->
    <?php if (!empty($images)): ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <?php foreach ($images as $img): ?>
          <img src="../<?= htmlspecialchars($img) ?>" 
               alt="Blog Image" 
               class="rounded-lg shadow-md border border-pink-300/30 hover:scale-105 transition-transform duration-300 cursor-pointer"
               onclick="openLightbox('../<?= htmlspecialchars($img) ?>')">
        <?php endforeach; ?>
  </div>
<?php elseif (!empty($post['image_path'])): ?>
  <img src="../<?= htmlspecialchars($post['image_path']) ?>" 
       class="w-full rounded-lg mb-4 shadow-md border border-pink-300/30">
<?php endif; ?>


    <article class="prose max-w-none text-pink-50 leading-relaxed mb-8">
      <?= $Parsedown->text($post['content']) ?>
    </article>

    <!-- ✅ Lightbox Modal -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
      <img id="lightboxImage" src="" alt="Expanded Image">
    </div>
    
    <!-- Like Button -->
    <form method="POST" class="mb-6">
      <button type="submit" name="like_post" class="flex items-center gap-2 text-pink-400 hover:text-pink-300 transition">
        <span><?= $userLiked ? "💖" : "🤍" ?></span>
        <?= $userLiked ? "Unlike" : "Like" ?> (<?= $totalLikes ?>)
      </button>
    </form>

    <!-- Edit/Delete buttons -->
    <?php if ($loggedIn && $_SESSION['user_id'] == $post['user_id']): ?>
      <div class="mt-4 flex gap-3">
        <a href="edit.php?id=<?= $post['bid']; ?>"
           class="bg-pink-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-pink-700 transition">
           ✏️ Edit
        </a>
        <form method="POST" action="delete.php"
              onsubmit="return confirm('Are you sure you want to delete this post?');">
          <input type="hidden" name="id" value="<?= $post['bid']; ?>">
          <button type="submit"
                  class="bg-red-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-600 transition">
                  🗑️ Delete
          </button>
        </form>
      </div>
    <?php endif; ?>

    <hr class="my-8 border-pink-400/30">

    <!-- Comments -->
    <section>
      <h2 class="text-xl font-semibold mb-4 text-pink-300">💬 Comments (<?= count($all_comments) ?>)</h2>

      <?php if ($loggedIn): ?>
        <form method="POST" class="mb-6">
          <textarea name="comment_text" rows="3" placeholder="Write a comment..."></textarea>
          <button type="submit" class="btn-primary mt-2">Post Comment</button>
        </form>
      <?php else: ?>
        <p class="text-pink-200 mb-6">Please <a href="../index.php" class="text-pink-400 underline">sign in</a> to comment.</p>
      <?php endif; ?>

      <?php foreach ($all_comments as $c): ?>
        <div class="comment-box">
          <p class="text-sm text-pink-50">
            <strong class="text-pink-300"><?= htmlspecialchars($c['username']) ?>:</strong>
            <?= htmlspecialchars($c['comment']) ?>
          </p>
          <p class="text-xs text-pink-200"><?= date('F j, Y g:i A', strtotime($c['created_at'])) ?></p>
        </div>
      <?php endforeach; ?>
    </section>

    <script>
      function openLightbox(src) {
        document.getElementById("lightboxImage").src = src;
        document.getElementById("lightbox").style.display = "flex";
      }
      function closeLightbox() {
        document.getElementById("lightbox").style.display = "none";
      }
    </script>
  </main>
</body>
</html>
