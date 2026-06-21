<?php
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // 1. Check Admin (Users Table)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Admin Login Success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = 'admin';
            header("Location: dashboard.php");
            exit;
        }

        // 2. Check Student (Members Table)
        // Login via PRN, Barcode, or Email
        $stmt = $pdo->prepare("SELECT * FROM members WHERE prn_number = ? OR barcode_id = ? OR email = ?");
        $stmt->execute([$username, $username, $username]);
        $member = $stmt->fetch();

        if ($member) {
            if ($member['status'] === 'blocked') {
                $error = "Account is blocked. Contact Admin.";
            } elseif (password_verify($password, $member['password'])) {
                // Student Login Success
                $_SESSION['user_id'] = $member['id'];
                $_SESSION['username'] = $member['name'];
                $_SESSION['role'] = 'student';
                $_SESSION['member_id'] = $member['id'];
                
                header("Location: student_dashboard.php");
                exit;
            } else {
                $error = "Incorrect password.";
            }
        } else {
             // Not found as Admin or Student
             if (!$user) {
                 $error = "Invalid credentials.";
             }
        }
    } else {
        $error = "Please enter both Username/PRN and Password.";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Library System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

<div class="bg-white/95 p-10 rounded-2xl shadow-2xl w-full max-w-md text-center backdrop-blur-sm">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-800 mb-2">Welcome Back</h2>
        <p class="text-slate-500 text-sm">Login to access your library account</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-6 text-sm font-medium border border-red-100">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5 text-left">
        <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Username / PRN / Email</label>
            <input type="text" name="username" placeholder="Enter ID" required autofocus
                   class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all outline-none bg-slate-50">
        </div>
        
        <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Password</label>
            <input type="password" name="password" placeholder="••••••••" required
                   class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all outline-none bg-slate-50">
        </div>

        <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-3.5 rounded-xl font-bold hover:shadow-lg hover:opacity-90 transition-all transform hover:-translate-y-0.5">
            Login Now
        </button>
    </form>
    
    <div class="mt-6 text-xs text-slate-400 border-t border-slate-100 pt-4">
        <p class="mb-2"><strong>Students:</strong> Use your PRN or Barcode ID as username.</p>
        <p>New Student? <a href="register.php" class="text-purple-600 font-bold hover:underline">Register Here</a></p>
    </div>
</div>

</body>
</html>
