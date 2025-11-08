<?php

require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

//  Fetch user's profile picture
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profile_pic = $user && !empty($user['profile_pic']) ? "../" . $user['profile_pic'] : "../uploads/default-avatar.png";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title']);
  $content = trim($_POST['content']);
  $image_paths = [];

  //  Handle multiple image uploads
  if (!empty($_FILES['images']['name'][0])) {
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
      $type = $_FILES['images']['type'][$key];
      if (in_array($type, ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'])) {
        $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
        $newName = uniqid('img_') . '.' . $ext;
        $target = __DIR__ . '/../uploads/' . $newName;
        if (move_uploaded_file($tmp_name, $target)) {
          $image_paths[] = 'uploads/' . $newName;
        }
      }
    }
  }

  try {
    //  Insert blog post
    $stmt = $conn->prepare("INSERT INTO blogPost (user_id, title, content) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $title, $content]);
    $newPostId = $conn->lastInsertId();

    //  Insert all uploaded images
    if (!empty($image_paths)) {
      $imgStmt = $conn->prepare("INSERT INTO blog_images (post_id, image_path) VALUES (?, ?)");
      foreach ($image_paths as $path) {
        $imgStmt->execute([$newPostId, $path]);
      }
    }

    $message = "✅ Published successfully!";
    header("refresh:2;url=single.php?id=$newPostId");
  } catch (PDOException $e) {
    $message = "❌ Error: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Blog | Blog App</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
  <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: url('../assets/background.jpg') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      color: #f5f3ff;
    }

    .overlay {
      background: rgba(26, 16, 45, 0.85);
      min-height: 100vh;
    }

    .glass-card {
      background: rgba(46, 16, 101, 0.8);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(168, 85, 247, 0.3);
      border-radius: 1rem;
      padding: 2rem;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    }

    input, textarea {
      background-color: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(216, 180, 254, 0.3);
      color: #f5f3ff;
      border-radius: 8px;
      padding: 0.5rem;
      width: 100%;
    }

    input:focus, textarea:focus {
      outline: none;
      border-color: #f9a8d4;
      box-shadow: 0 0 10px #f9a8d4;
    }

    .btn-primary {
      background-color: #a21caf;
      color: white;
      font-weight: 600;
      padding: 0.5rem 1.5rem;
      border-radius: 0.5rem;
      transition: 0.3s;
      width: 100%;
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

    .editor-toolbar a { color: #f5f3ff !important; }
    .CodeMirror {
      background-color: #2e1065 !important;
      color: #f5f3ff !important;
      border-radius: 0.5rem;
    }
    .editor-toolbar {
      background-color: rgba(168, 85, 247, 0.15);
      border-radius: 8px 8px 0 0;
    }
  </style>
</head>

<body class="fade-in">
<div class="overlay">

  <!-- Navbar -->
  <nav class="bg-[#2e1065]/70 backdrop-blur-md border-b border-purple-700 shadow-lg sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center">

      <!--  Styled Back Button -->
      <a href="blogs.php"
         class="bg-pink-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-pink-700 transition shadow-md">
         👈 Back to Blogs
      </a>

      <h1 class="text-2xl font-semibold text-pink-300">Create Blog</h1>

      <!-- ✅ User’s profile picture -->
      <a href="profile.php">
        <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile"
             class="w-9 h-9 rounded-full border-2 border-pink-400 object-cover">
      </a>
    </div>
  </nav>

  <!-- Main Form -->
  <main class="max-w-3xl mx-auto glass-card mt-10">
    <?php if ($message): ?>
      <div class="mb-4 bg-pink-100/10 text-pink-300 px-3 py-2 rounded"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <div>
        <label class="block text-sm font-medium text-pink-200 mb-1">Title</label>
        <input type="text" name="title" required>
      </div>

      <div>
        <label class="block text-sm font-medium text-pink-200 mb-1">Upload Images (You can select multiple)</label>
        <input type="file" name="images[]" accept="image/*" multiple class="text-sm">
      </div>

      <div>
        <label class="block text-sm font-medium text-pink-200 mb-1">Content</label>
        <textarea name="content" id="editor" rows="10"></textarea>
      </div>

      <button type="submit" class="btn-primary">🚀 Publish Blog</button>
    </form>
  </main>

</div>

<!--  EasyMDE Editor Script -->

<script>
  const editor = new EasyMDE({
    element: document.getElementById("editor"),
    spellChecker: false,
    placeholder: "Write your blog content here... (supports Markdown)",
    toolbar: [
      "bold", "italic", "heading", "|",
      "quote", "unordered-list", "ordered-list", "|",
      "link", "image", "code", "table", "|",
      "preview", "side-by-side", "fullscreen", "|",
      "guide"
    ],
    renderingConfig: { codeSyntaxHighlighting: true }
  });

  document.querySelector("form").addEventListener("submit", function() {
    document.getElementById("editor").value = editor.value();
  });
</script>
</body>
</html>
