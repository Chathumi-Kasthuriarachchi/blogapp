<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) die("Unauthorized access.");

$id = intval($_POST['id'] ?? 0);

// Verify ownership
$stmt = $conn->prepare("SELECT user_id FROM blogpost WHERE bid=?");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) die("Post not found.");
if ($post['user_id'] != $_SESSION['user_id']) die("You can only delete your own posts.");

  //  Try to delete the post
try {
  $del = $conn->prepare("DELETE FROM blogpost WHERE bid=?");
  $del->execute([$id]);

  
    //  Show success message and redirect back to blogs page
  echo "<script>alert('🗑️ Post deleted successfully!');window.location='blogs.php';</script>";
  exit();

} catch (PDOException $e) {
    //  Handle database errors
  echo "❌ DB Error: ".$e->getMessage();
}
?>
