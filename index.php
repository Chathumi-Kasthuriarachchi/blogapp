<?php
session_start();

// Optional: greet user if logged in
$username = $_SESSION['username'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>BlogHaven - Connect, create, and share your world.</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- AOS Animation Library -->
  <link href="https://unpkg.com/aos@next/dist/aos.css" rel="stylesheet" />
  <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <link rel="stylesheet" href="public/css/style.css">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #1e1b4b, #3b0764);
      color: white;
      overflow-x: hidden;
    }
    .glow {
      box-shadow: 0 0 15px rgba(168, 85, 247, 0.6);
    }
    
  </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center text-center px-6">
    
<div id="particles-js"></div>

  <!-- Navbar -->
  <nav class="w-full flex justify-between items-center py-5 px-8 fixed top-0 bg-[#1e1b4b]/90 backdrop-blur-md shadow-lg">
    <h1 class="text-4xl font-bold text-purple-400 pulse-text fancy-font">BLOGHAVEN</h1>
    <div class="flex items-center gap-4">
      <button id="openAuth" class="bg-purple-500 hover:bg-purple-600 px-4 py-2 rounded-lg text-white font-semibold transition-all">Login</button>
      <button id="openSignup" class="bg-pink-500 hover:bg-pink-600 px-4 py-2 rounded-lg text-white font-semibold transition-all">Sign Up</button>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="flex flex-col justify-center items-center mt-32" data-aos="fade-up">
    <h1 class="text-5xl sm:text-6xl font-extrabold mb-4 text-white">
      Blog Your <span class="text-purple-400">Journey</span>,<br> Inspire a <span class="text-pink-400">Movement</span>
    </h1>
    <p class="text-gray-300 max-w-2xl mb-8">
      Your words can shape perspectives, heal hearts, and ignite change — it all begins with a single post.  </p>
    <div class="flex gap-4">
      <button id="openAuth2" class="bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-lg font-semibold glow transition-transform hover:scale-105">
        Write Now
      </button>
      <a href="views/blogs.php" class="bg-pink-600 hover:bg-pink-700 px-6 py-3 rounded-lg font-semibold glow transition-transform hover:scale-105">
        Discover Blogs
      </a>
    </div>
  </section>

  <!-- Footer -->
    <?php include 'includes/footer.php'; ?>


  <!-- Modal (Login + Signup) -->
  <div id="authModal" class="fixed inset-0 bg-black/60 hidden flex justify-center items-center">
    <div class="bg-white text-black rounded-2xl shadow-2xl w-11/12 sm:w-96 p-6 relative">
      <button id="closeAuth" class="absolute top-3 right-4 text-gray-500 text-xl">✕</button>
      <h2 id="authTitle" class="text-2xl font-semibold mb-4 text-center text-purple-600">Login</h2>
      
      <!-- Login Form -->
      <form id="loginForm" method="POST" action="views/login.php" class="space-y-4">
        <div>
          <label class="block text-sm font-medium">Email</label>
          <input type="email" name="email" required class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
        </div>
        <div>
          <label class="block text-sm font-medium">Password</label>
          <input type="password" name="password" required class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
        </div>
        <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">Login</button>
        <p class="text-sm text-center mt-2">Don't have an account? <a href="#" id="switchToSignup" class="text-pink-600 hover:underline">Sign up</a></p>
      </form>

      <!-- Signup Form -->
      <form id="signupForm" method="POST" action="views/register.php" class="space-y-4 hidden">
        <div>
          <label class="block text-sm font-medium">Username</label>
          <input type="text" name="username" required class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-pink-500">
        </div>
        <div>
          <label class="block text-sm font-medium">Email</label>
          <input type="email" name="email" required class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-pink-500">
        </div>
        <div>
          <label class="block text-sm font-medium">Password</label>
          <input type="password" name="password" required class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-pink-500">
        </div>
        <button type="submit" class="w-full bg-pink-600 text-white py-2 rounded-lg hover:bg-pink-700">Sign Up</button>
        <p class="text-sm text-center mt-2">Already have an account? <a href="#" id="switchToLogin" class="text-purple-600 hover:underline">Login</a></p>
      </form>
    </div>
  </div>

  <!-- JS for modal + AOS -->
  <script>
    AOS.init({ duration: 800, once: true });
    
    const modal = document.getElementById("authModal");
    const loginForm = document.getElementById("loginForm");
    const signupForm = document.getElementById("signupForm");
    const authTitle = document.getElementById("authTitle");

    const openBtns = [document.getElementById("openAuth"), document.getElementById("openAuth2")];
    const signupBtn = document.getElementById("openSignup");
    const closeBtn = document.getElementById("closeAuth");

    openBtns.forEach(btn => btn.addEventListener("click", () => {
      modal.classList.remove("hidden");
      loginForm.classList.remove("hidden");
      signupForm.classList.add("hidden");
      authTitle.textContent = "Login";
    }));

    signupBtn.addEventListener("click", () => {
      modal.classList.remove("hidden");
      loginForm.classList.add("hidden");
      signupForm.classList.remove("hidden");
      authTitle.textContent = "Sign Up";
    });

    document.getElementById("switchToSignup").addEventListener("click", () => {
      loginForm.classList.add("hidden");
      signupForm.classList.remove("hidden");
      authTitle.textContent = "Sign Up";
    });

    document.getElementById("switchToLogin").addEventListener("click", () => {
      signupForm.classList.add("hidden");
      loginForm.classList.remove("hidden");
      authTitle.textContent = "Login";
    });

    closeBtn.addEventListener("click", () => modal.classList.add("hidden"));
  </script>
  <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

  <script>
particlesJS("particles-js", {
  "particles": {
    "number": {
      "value": 80,
      "density": {
        "enable": true,
        "value_area": 800
      }
    },
    "color": { "value": "#ffffff" },   // white particles
    "shape": {
      "type": "circle"
    },
    "opacity": {
      "value": 0.5,
      "random": false
    },
    "size": {
      "value": 3,
      "random": true
    },
    "line_linked": {
      "enable": true,
      "distance": 150,
      "color": "#ffffff",
      "opacity": 0.4,
      "width": 1
    },
    "move": {
      "enable": true,
      "speed": 3,
      "direction": "none",
      "random": false,
      "out_mode": "out"
    }
  },
  "interactivity": {
    "detect_on": "canvas",
    "events": {
      "onhover": { "enable": true, "mode": "repulse" },
      "onclick": { "enable": true, "mode": "push" },
      "resize": true
    },
    "modes": {
      "repulse": { "distance": 100, "duration": 0.4 },
      "push": { "particles_nb": 4 }
    }
  },
  "retina_detect": true
});
</script>


</body>
</html>
