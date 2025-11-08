<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) die("Unauthorized access.");

$bid = intval($_GET['id'] ?? 0);

// Fetch post
$stmt = $conn->prepare("SELECT * FROM blogpost WHERE bid = ?");
$stmt->execute([$bid]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) die("Post not found.");
if ($post['user_id'] != $_SESSION['user_id']) die("You can only edit your own posts.");

// ✅ Fetch existing images for this blog
$imgStmt = $conn->prepare("SELECT imgid, image_path FROM blog_images WHERE post_id = ?");
$imgStmt->execute([$bid]);
$existingImages = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title']);
  $content = trim($_POST['content']);

  // --- Delete selected images ---
  if (!empty($_POST['delete_images'])) {
    foreach ($_POST['delete_images'] as $imgPath) {
      $del = $conn->prepare("DELETE FROM blog_images WHERE post_id = ? AND image_path = ?");
      $del->execute([$bid, $imgPath]);
      @unlink("../" . $imgPath); // Delete file physically
    }
  }

  // --- Upload new images ---
  if (!empty($_FILES['images']['name'][0])) {
    foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
      $type = $_FILES['images']['type'][$i];
      if (in_array($type, ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'])) {
        $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
        $newName = uniqid('img_') . '.' . $ext;
        $target = __DIR__ . '/../uploads/' . $newName;
        if (move_uploaded_file($tmp, $target)) {
          $path = 'uploads/' . $newName;
          $conn->prepare("INSERT INTO blog_images (post_id, image_path) VALUES (?, ?)")->execute([$bid, $path]);
        }
      }
    }
  }

  // --- Update text content ---
  $stmt = $conn->prepare("UPDATE blogpost SET title=?, content=? WHERE bid=?");
  $stmt->execute([$title, $content, $bid]);

  echo "<script>alert('✅ Post updated successfully!');window.location='single.php?id=$bid';</script>";
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Blog Post</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
  <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: url('../assets/background.jpg') no-repeat center center fixed;
      background-size: cover;
      color: #f5f3ff;
      min-height: 100vh;
    }
    .overlay {
      background: rgba(26, 16, 45, 0.88);
      min-height: 100vh;
      padding: 2rem;
    }
    .glass-card {
      background: rgba(46, 16, 101, 0.8);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(168, 85, 247, 0.3);
      border-radius: 1rem;
      padding: 2rem;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
      max-width: 800px;
      margin: auto;
    }
    .btn-primary {
      background-color: #a21caf;
      color: white;
      font-weight: 600;
      padding: 0.5rem 1.5rem;
      border-radius: 0.5rem;
      transition: 0.3s;
    }
    .btn-primary:hover { background-color: #db2777; transform: scale(1.05); }
    .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
    .image-item { position: relative; }
    .image-item img {
      width: 100%; height: 100px; object-fit: cover;
      border-radius: 8px; border: 2px solid rgba(168,85,247,0.5);
      transition: transform 0.3s;
    }
    .image-item:hover img { transform: scale(1.05); }
    .image-item label {
      position: absolute; inset: 0;
      background: rgba(0,0,0,0.5); color: #fff;
      display: flex; align-items: center; justify-content: center;
      opacity: 0; border-radius: 8px;
      cursor: pointer; transition: opacity 0.3s;
    }
    .image-item:hover label { opacity: 1; }
    .CodeMirror, .editor-toolbar {
      background-color: #2e1065 !important;
      color: #fff !important;
    }
  </style>
</head>

<body>
  <!-- ✅ Navigation Bar -->
  <nav class="bg-[#2e1065]/70 backdrop-blur-md border-b border-purple-700 shadow-lg sticky top-0 z-10">
    <div class="max-w-6xl mx-auto flex justify-between items-center px-4 py-3">
      <a href="blogs.php" class="bg-pink-600 text-white text-white px-4 py-2 rounded-lg hover:bg-pink-700 transition shadow-md">
        👈 Back to Blogs
      </a>
      <h1 class="text-2xl font-bold text-pink-300">Edit Blog</h1>

      <?php 
        // Get profile picture path
        $profilePicPath = isset($_SESSION['profile_pic']) && !empty($_SESSION['profile_pic']) 
            ? '../' . htmlspecialchars($_SESSION['profile_pic'])
            : '../uploads/default-avatar.png';
        ?>
        <a href="profile.php" class="relative group" title="Profile">
        <img 
            src="<?= $profilePicPath ?>" 
            alt="Profile"
            onerror="this.src='../uploads/default-avatar.png';"
            class="w-10 h-10 rounded-full border-2 border-pink-400 object-cover shadow-md transition-all duration-300 
                group-hover:shadow-pink-500/70 group-hover:scale-110">
        </a>

    </div>
  </nav>

  <div class="overlay">
    <div class="glass-card fade-in">
      <h2 class="text-3xl font-bold mb-6 text-pink-400">✏️ Edit Blog Post</h2>

      <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <div>
          <label class="block text-sm mb-1 text-pink-200">Title</label>
          <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" class="w-full rounded px-3 py-2" required>
        </div>

        <!-- ✅ Show existing images -->
        <div>
          <label class="block text-sm mb-1 text-pink-200">Existing Images</label>
          <?php if (!empty($existingImages)): ?>
            <div class="image-grid">
              <?php foreach ($existingImages as $img): ?>
                <div class="image-item">
                  <img src="../<?= htmlspecialchars($img['image_path']) ?>" alt="Blog image">
                  <label>
                    <input type="checkbox" name="delete_images[]" value="<?= htmlspecialchars($img['image_path']) ?>" class="hidden">
                    🗑️ Delete
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-pink-200">No images uploaded yet.</p>
          <?php endif; ?>
        </div>

        <!-- Upload new images -->
        <div>
          <label class="block text-sm mb-1 text-pink-200">Add New Images</label>
          <input type="file" name="images[]" multiple accept="image/*">
        </div>

        <!-- Markdown content -->
        <div>
          <label class="block text-sm mb-1 text-pink-200">Content</label>
          <textarea name="content" id="editor" rows="10"><?= htmlspecialchars($post['content']) ?></textarea>
        </div>

        <button type="submit" class="btn-primary">💾 Save Changes</button>
        <a href="single.php?id=<?= $bid ?>" class="ml-3 text-pink-200 hover:underline">Cancel</a>
      </form>
    </div>
  </div>

  <script>
    // Markdown editor (same as create.php)
    const editor = new EasyMDE({
      element: document.getElementById("editor"),
      spellChecker: false,
      toolbar: [
        "bold", "italic", "heading", "|",
        "quote", "unordered-list", "ordered-list", "|",
        "link", "image", "table", "code", "|",
        "preview", "side-by-side", "fullscreen", "|", "guide"
      ],
      renderingConfig: { codeSyntaxHighlighting: true }
    });
  </script>
</body>
</html>
