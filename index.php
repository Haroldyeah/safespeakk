<?php
$pageTitle = 'SafeSpeak - Speak Up, Stay Safe';
require_once 'config/config.php';

// Redirect based on user status
if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {  
        case 'student':
            header('Location: student/dashboard.php');
            exit;
        case 'school':
            header('Location: school/dashboard.php');
            exit;
        case 'admin':
            header('Location: admin/dashboard.php');
            exit;
    }
}

require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($pageTitle) ? $pageTitle : 'SafeSpeak'; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-b from-gray-50 to-white text-gray-800 font-sans flex flex-col items-center">

  <!-- Navbar -->
  <nav class="w-full bg-white/60 backdrop-blur sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
      <a href="index.php" class="flex items-center gap-3">
        <div class="h-10 w-10 bg-indigo-600 text-white rounded-lg flex items-center justify-center font-bold">SS</div>
        <span class="font-semibold text-lg">SafeSpeak</span>
      </a>
      <div class="hidden md:flex items-center gap-4">
        <a href="#schools" class="text-gray-600 hover:text-indigo-600 transition">Schools</a>
        <a href="#about" class="text-gray-600 hover:text-indigo-600 transition">About</a>
        <a href="#features" class="text-gray-600 hover:text-indigo-600 transition">Features</a>
        <a href="auth/login.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700 transition">Sign In</a>
      </div>
      <!-- Mobile menu button -->
      <div class="md:hidden flex items-center gap-2">
        <button id="mobileMenuButton" aria-label="Open menu" class="p-2 rounded-md text-indigo-600 bg-white/20 hover:bg-white/30">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a href="auth/login.php" class="bg-indigo-600 text-white px-3 py-2 rounded-lg">Sign In</a>
      </div>
    </div>
    <!-- Mobile menu (hidden by default) -->
    <div id="mobileMenu" class="md:hidden hidden border-t border-white/10">
      <div class="px-4 py-3 flex flex-col gap-2">
        <a href="#schools" class="text-gray-700 hover:text-indigo-600">Schools</a>
        <a href="#about" class="text-gray-700 hover:text-indigo-600">About</a>
        <a href="#features" class="text-gray-700 hover:text-indigo-600">Features</a>
        <a href="auth/login.php" class="text-indigo-600 font-medium">Sign In</a>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="min-h-[85vh] flex items-center justify-center">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
      <div class="flex flex-col lg:flex-row items-center justify-center gap-12">
        <div class="space-y-6 max-w-xl text-center lg:text-left">
        <div class="inline-flex items-center gap-3 bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-sm font-medium">Your Voice. Your Safety.</div>
        <h1 class="text-4xl md:text-5xl font-extrabold leading-tight text-gray-900">SafeSpeak — Speak Up, Stay Safe</h1>
        <p class="text-lg text-gray-600">A confidential reporting platform for students in Poblacion, Daanbantayan. Report incidents, upload evidence, and access support — quickly and safely.</p>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 justify-center lg:justify-start">
          <a href="#report" class="bg-indigo-600 text-white px-6 py-3 rounded-lg shadow hover:bg-indigo-700 transition w-full sm:w-auto text-center">Start a Report</a>
          <a href="#features" class="text-indigo-600 border border-indigo-100 px-5 py-3 rounded-lg hover:bg-indigo-50 transition w-full sm:w-auto text-center">See Features</a>
        </div>
        <div class="flex gap-4 mt-2 items-center justify-center lg:justify-start">
          <div class="text-sm text-gray-500">Trusted by</div>
          <div class="flex items-center gap-3">
            <img src="uploads/dnhs.jpg" alt="Daanbantayan" class="h-8 w-8 md:h-10 md:w-10 rounded-md object-cover shadow-sm" />
            <img src="uploads/bma.jpg" alt="BMA" class="h-8 w-8 md:h-10 md:w-10 rounded-md object-cover shadow-sm" />
            <img src="uploads/go.jpg" alt="CPG" class="h-8 w-8 md:h-10 md:w-10 rounded-md object-cover shadow-sm" />
          </div>
        </div>
      </div>
      <div class="relative">
        <div class="rounded-2xl overflow-hidden shadow-2xl">
          <img src="uploads/a9778a1f-b9f9-4aab-a19e-af2908dcc7c1.jpg" alt="School counselor and student" class="w-full h-64 md:h-96 object-cover" />
        </div>
        <div class="absolute -bottom-6 left-6 bg-white rounded-xl p-4 shadow-md w-64 max-w-[60%] md:max-w-[100%]">
          <div class="text-sm text-gray-500">Reports received</div>
          <div class="text-2xl font-bold text-indigo-600">1,248</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Participating Schools Section -->
  <section id="schools" class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <div class="max-w-3xl mx-auto mb-12">
        <h2 class="text-3xl font-semibold mb-8 text-gray-900">Participating Schools</h2>
      <p class="text-gray-600 mb-8">Proud partners helping keep students safe.</p>
      <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-6">
        <!-- School 1 -->
        <div class="group bg-white rounded-2xl overflow-hidden shadow hover:shadow-xl transition">
          <div class="relative h-44">
            <img src="uploads/dnhs.jpg" alt="School 1" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
          </div>
          <div class="p-4 text-left">
            <h3 class="text-lg font-semibold text-gray-800">Daanbantayan National High School</h3>
            <p class="text-gray-500 text-sm mt-1">Empowering students to speak up and stay safe.</p>
          </div>
        </div>
        <!-- School 2 -->
        <div class="group bg-white rounded-2xl overflow-hidden shadow hover:shadow-xl transition">
          <div class="relative h-44">
            <img src="uploads/bma.jpg" alt="School 2" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
          </div>
          <div class="p-4 text-left">
            <h3 class="text-lg font-semibold text-gray-800">Bright Minds in Action Learning Village</h3>
            <p class="text-gray-500 text-sm mt-1">A safe space for learners to report issues.</p>
          </div>
        </div>
        <!-- School 3 -->
        <div class="group bg-white rounded-2xl overflow-hidden shadow hover:shadow-xl transition">
          <div class="relative h-44">
            <img src="uploads/sla.png" alt="School 3" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
          </div>
          <div class="p-4 text-left">
            <h3 class="text-lg font-semibold text-gray-800">St. Louisse Academy, Inc. - Daanbantayan Campus</h3>
            <p class="text-gray-500 text-sm mt-1">Fostering safety, support, and understanding.</p>
          </div>
        </div>
         <!-- School 4 -->
        <div class="group bg-white rounded-2xl overflow-hidden shadow hover:shadow-xl transition">
          <div class="relative h-44">
            <img src="uploads/smpa.jpg" alt="School 4" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
          </div>
          <div class="p-4 text-left">
            <h3 class="text-lg font-semibold text-gray-800">Academia de San Martin</h3>
            <p class="text-gray-500 text-sm mt-1">Creating change through empowered reporting.</p>
          </div>
        </div>
        <!-- School 5 -->
        <div class="group bg-white rounded-2xl overflow-hidden shadow hover:shadow-xl transition">
          <div class="relative h-44">
            <img src="uploads/dover.jpg" alt="School 5" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
          </div>
          <div class="p-4 text-left">
            <h3 class="text-lg font-semibold text-gray-800">Dover Academic Center for Excellence, Inc.</h3>
            <p class="text-gray-500 text-sm mt-1">Supportive environment for every learner.</p>
          </div>
        </div>
        <!-- School 6 -->
        <div class="group bg-white rounded-2xl overflow-hidden shadow hover:shadow-xl transition">
          <div class="relative h-44">
            <img src="uploads/go.jpg" alt="School 6" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
          </div>
          <div class="p-4 text-left">
            <h3 class="text-lg font-semibold text-gray-800">Constancio P. Go Memorial Learning Center</h3>
            <p class="text-gray-500 text-sm mt-1">Trusted by students and educators alike.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <section id="about" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-10 items-center">
      <div class="max-w-xl">
        <h2 class="text-3xl font-semibold text-gray-800 mb-4">What is SafeSpeak?</h2>
  <p class="text-gray-600 mb-4">SafeSpeak is a web-based platform designed for high schools in Poblacion, Daanbantayan, Cebu, empowering students to report bullying, harassment, or any form of violence. Our goal is to ensure mental and emotional well-being through secure, account-linked reporting and action-driven follow-up.</p>
        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-gray-700 mt-6">
          <li class="flex items-start gap-3"><span class="text-indigo-600 font-bold">•</span> Confidential reporting</li>
          <li class="flex items-start gap-3"><span class="text-indigo-600 font-bold">•</span> Real-time analytics</li>
          <li class="flex items-start gap-3"><span class="text-indigo-600 font-bold">•</span> AI mental health support</li>
          <li class="flex items-start gap-3"><span class="text-indigo-600 font-bold">•</span> Evidence upload</li>
        </ul>
      </div>
    <div class="rounded-2xl overflow-hidden shadow-lg">
  <img src="uploads/about-students.png" loading="lazy" class="w-full h-56 md:h-80 object-cover" alt="Students and teacher collaborating in a classroom" />
    </div>
    </div>
  </section>

  <!-- Features -->
  <section id="features" class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <div class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-semibold mb-6 text-gray-900">Core Features</h2>
      <p class="text-gray-600 mb-8">Tools designed to protect, support, and empower students and educators.</p>
      <div class="grid md:grid-cols-3 gap-8 text-left">
        <div class="p-6 bg-white rounded-2xl shadow hover:shadow-xl transition transform hover:-translate-y-1">
          <div class="text-indigo-600 text-3xl mb-3">🔒</div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Secure Reporting</h3>
          <p class="text-gray-600">Submit reports linked to your account so school staff can follow up and provide support.</p>
        </div>
        <div class="p-6 bg-white rounded-2xl shadow hover:shadow-xl transition transform hover:-translate-y-1">
          <div class="text-indigo-600 text-3xl mb-3">🤖</div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">AI Chat Support</h3>
          <p class="text-gray-600">Access immediate, compassionate support any time with our chatbot.</p>
        </div>
        <div class="p-6 bg-white rounded-2xl shadow hover:shadow-xl transition transform hover:-translate-y-1">
          <div class="text-indigo-600 text-3xl mb-3">📊</div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Admin Dashboard</h3>
          <p class="text-gray-600">Manage reports, assign actions, and track outcomes with clarity.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials -->
  <section class="py-20 bg-white">
    <div class="max-w-5xl mx-auto px-6 text-center">
      <h2 class="text-3xl font-semibold mb-6 text-gray-900">What Students Say</h2>
      <p class="text-gray-600 mb-8">Real feedback from learners who felt heard and supported.</p>
      <div class="grid md:grid-cols-2 gap-8">
        <div class="bg-indigo-50 p-6 rounded-2xl shadow text-left">
          <p class="text-gray-800 italic">"SafeSpeak helped me speak up without fear. The support was instant and real."</p>
          <div class="mt-4 text-sm text-gray-500">— Grade 10 Student</div>
        </div>
        <div class="bg-indigo-50 p-6 rounded-2xl shadow text-left">
          <p class="text-gray-800 italic">"I never thought reporting could be this easy. I feel protected now."</p>
          <div class="mt-4 text-sm text-gray-500">— Grade 9 Student</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Final CTA -->
  <section class="relative py-24 bg-gradient-to-r from-indigo-600 to-indigo-500 text-white" id="report">
    <!-- Decorative wave -->
    <svg class="absolute inset-x-0 -top-6 w-full" viewBox="0 0 1440 120" xmlns="http://www.w3.org/2000/svg"><path fill="#eef2ff" d="M0,64L48,69.3C96,75,192,85,288,106.7C384,128,480,160,576,165.3C672,171,768,149,864,122.7C960,96,1056,64,1152,58.7C1248,53,1344,75,1392,85.3L1440,96L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path></svg>
    <div class="max-w-4xl mx-auto px-6 relative z-10">
      <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-3xl p-8 md:p-12 text-center shadow-2xl">
        <div class="inline-flex items-center justify-center bg-white/10 text-white/90 px-3 py-1 rounded-full mb-4 text-sm font-medium">Confidential & Secure</div>
        <h2 class="text-3xl md:text-4xl font-extrabold mb-4">Ready to speak up?</h2>
        <p class="text-indigo-100 text-lg md:text-xl mb-6">Start your confidential report now — it's safe, simple, and supported by trained staff.</p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <a href="auth/login.php" class="inline-flex items-center gap-3 bg-white text-indigo-600 font-semibold px-6 py-3 rounded-full shadow hover:shadow-lg transition">
            <!-- icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A1 1 0 0017.5 4h-15a1 1 0 00-.497.884z" /></svg>
            Start Reporting
          </a>
          <a href="#about" class="inline-flex items-center gap-3 px-6 py-3 rounded-full border border-white/30 text-white hover:bg-white/10 transition">
            <!-- info icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-8-4a1 1 0 100 2 1 1 0 000-2zm1 8H9v-1h1v-3H9V9h3v5z" clip-rule="evenodd"/></svg>
            How it works
          </a>
        </div>

        <div class="mt-6 text-sm text-indigo-100/80 flex flex-col sm:flex-row items-center justify-center gap-4">
          <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-white/80"></span> Reports are linked to reporter accounts for follow-up</div>
          <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-white/80"></span> Evidence upload supported</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-white border-t py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col md:flex-row justify-between items-center text-sm text-gray-600">
        <div class="mb-4 md:mb-0">
        <div class="font-semibold text-gray-800">SafeSpeak</div>
        <div class="text-gray-500">Poblacion, Daanbantayan, Cebu</div>
      </div>
      <div class="flex space-x-4">
        <a href="#" class="hover:text-indigo-600">Privacy Policy</a>
        <a href="#" class="hover:text-indigo-600">Contact</a>
        <a href="auth/login.php" class="text-indigo-600 font-medium">Sign In</a>
      </div>
      <div class="mt-4 md:mt-0 text-gray-500">© 2025 SafeSpeak. All rights reserved.</div>
    </div>
  </footer>

</body>
</html>



<script>
  // Mobile menu toggle
  (function(){
    var btn = document.getElementById('mobileMenuButton');
    var menu = document.getElementById('mobileMenu');
    if (!btn || !menu) return;
    btn.addEventListener('click', function(){
      if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
      } else {
        menu.classList.add('hidden');
      }
    });
  })();
</script>