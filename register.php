<?php
require_once 'config/db.php';

// Include Barcode Logic
if (!class_exists('SimpleBarcode')) {
    include_once 'lib_barcode.php'; 
}

$message = '';
$error = '';
$generated_id_card = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $admission_year = trim($_POST['admission_year']);
    $prn_number = trim($_POST['prn_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validation
    if (empty($name) || empty($prn_number) || empty($password)) {
        $error = "Name, PRN, and Password are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Generate a unique Barcode ID
        $barcode_id = 'STU' . rand(100000, 999999);
        
        // Hash Password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Handle Photo Upload
        $photo_path = '';
        $upload_dir = 'uploads/members/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                $photo_path = $upload_dir . $barcode_id . '.' . $file_ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
            }
        }

        try {
            // Check if PRN or Email already exists
            $stmt = $pdo->prepare("SELECT id FROM members WHERE prn_number = ? OR email = ?");
            $stmt->execute([$prn_number, $email]);
            if ($stmt->fetch()) {
                $error = "User with this PRN or Email already exists.";
            } else {
                // Insert into DB
                $stmt = $pdo->prepare("
                    INSERT INTO members (barcode_id, name, email, phone, department, admission_year, prn_number, password, photo_path, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([$barcode_id, $name, $email, $phone, $department, $admission_year, $prn_number, $hashed_password, $photo_path]);

                // Generate ID Card
                $cards = generateIDCards($barcode_id, $name, $department, $admission_year, $prn_number, $photo_path, '', $phone);
                
                // Update ID Card Path
                if ($cards['front']) {
                    $stmt = $pdo->prepare("UPDATE members SET id_card_path = ? WHERE barcode_id = ?");
                    $stmt->execute([$cards['front'], $barcode_id]);
                }

                $message = "Registration successful! Your Library ID is $barcode_id.";
            }
        } catch (Exception $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

// Helper function for ID Card (Duplicated from add_member.php for standalone usage)
function generateIDCards($barcode, $name, $dept, $year, $prn, $photo_path, $address, $phone) {
    // Basic setup similar to add_member.php
    $width = 600; $height = 380;
    $img = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $blue = imagecolorallocate($img, 37, 99, 235);
    
    // Background and base styles
    imagefilledrectangle($img, 0, 0, $width, $height, imagecolorallocate($img, 248, 250, 252));
    imagefilledrectangle($img, 0, 0, $width, 90, $blue);
    imagerectangle($img, 10, 10, $width-10, $height-10, $blue);
    
    imagestring($img, 5, 150, 25, "UNIVERSITY LIBRARY", $white);
    imagestring($img, 4, 180, 50, "STUDENT IDENTITY CARD", $white);
    
    // Photo
    if ($photo_path && file_exists($photo_path)) {
        $ext = strtolower(pathinfo($photo_path, PATHINFO_EXTENSION));
        $src = ($ext == 'png') ? imagecreatefrompng($photo_path) : imagecreatefromjpeg($photo_path);
        if ($src) {
            imagecopyresampled($img, $src, 25, 110, 0, 0, 110, 130, imagesx($src), imagesy($src));
            imagedestroy($src);
        }
    } else {
        imagerectangle($img, 25, 110, 135, 240, $black);
        imagestring($img, 3, 50, 160, "No Photo", $black);
    }
    
    // Text
    $text_x = 160; $y = 110; $lh = 28;
    imagestring($img, 5, $text_x, $y, strtoupper($name), $black);
    imagestring($img, 4, $text_x, $y + $lh*1.5, "ID No   : " . $barcode, $black);
    imagestring($img, 4, $text_x, $y + $lh*2.5, "Dept    : " . $dept, $black);
    imagestring($img, 4, $text_x, $y + $lh*3.5, "Year    : " . $year, $black);
    imagestring($img, 4, $text_x, $y + $lh*4.5, "PRN     : " . $prn, $black);
    
    // Barcode
    if (class_exists('SimpleBarcode')) {
       SimpleBarcode::draw($img, 220, $height - 60, $barcode, $black, 40, 2);
    }
    
    $save_dir = 'uploads/id_cards/';
    if (!is_dir($save_dir)) mkdir($save_dir, 0777, true);
    
    $file_front = $save_dir . 'ID_FRONT_' . $barcode . '.png';
    imagepng($img, $file_front);
    imagedestroy($img);

    // --- BACK SIDE ---
    $img_back = imagecreatetruecolor($width, $height);
    imagefilledrectangle($img_back, 0, 0, $width, $height, $white);
    
    // Border
    imagerectangle($img_back, 10, 10, $width-10, $height-10, $blue);
    
    // Header
    imagefilledrectangle($img_back, 0, 0, $width, 50, $blue);
    imagestring($img_back, 4, 30, 15, "TERMS AND CONDITIONS", $white);
    
    // Content
    $rules = [
        "1. Non-transferable card.",
        "2. Must present while borrowing.",
        "3. Report loss immediately.",
        "4. Valid only for course duration.",
        "5. Return if found."
    ];
    
    $y_rule = 80;
    foreach ($rules as $rule) {
        imagestring($img_back, 4, 30, $y_rule, $rule, $black);
        $y_rule += 30;
    }
    
    // Address / Contact
    $y_rule += 20;
    imagestring($img_back, 4, 30, $y_rule, "Address:", imagecolorallocate($img_back, 30, 64, 175));
    imagestring($img_back, 3, 30, $y_rule + 20, "University Library Main Campus,", imagecolorallocate($img_back, 71, 85, 105));
    imagestring($img_back, 3, 30, $y_rule + 35, "City - 400001", imagecolorallocate($img_back, 71, 85, 105));
    imagestring($img_back, 3, 30, $y_rule + 50, "Ph: " . ($phone ? $phone : "0123-456789"), imagecolorallocate($img_back, 71, 85, 105));
    
    $file_back = $save_dir . 'ID_BACK_' . $barcode . '.png';
    imagepng($img_back, $file_back);
    imagedestroy($img_back);
    
    return ['front' => $file_front, 'back' => $file_back];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Library System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center py-10 px-4">

<div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-2xl border border-slate-100">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-slate-800">Student Registration</h1>
        <p class="text-slate-500 mt-2">Create your library account to borrow books</p>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl mb-6 text-center">
            <p class="font-bold text-lg mb-2"><?php echo $message; ?></p>
            <p>You can now <a href="login.php" class="underline font-bold hover:text-green-900">Login Here</a> using your PRN.</p>
        </div>
    <?php elseif ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-xl mb-6 text-center">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (!$message): ?>
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Personal Info -->
            <div class="col-span-2">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Personal Details</h3>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">PRN Number</label>
                <input type="text" name="prn_number" required placeholder="University PRN" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none font-mono">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Department</label>
                <select name="department" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Select Department</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Information Tech">Information Tech</option>
                    <option value="Mechanical">Mechanical</option>
                    <option value="Civil">Civil</option>
                    <option value="Electrical">Electrical</option>
                    <option value="Electronics">Electronics</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Admission Year</label>
                <input type="number" name="admission_year" min="2020" max="2030" value="<?php echo date('Y'); ?>" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
                <input type="email" name="email" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Phone</label>
                <input type="tel" name="phone" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <!-- Security -->
            <div class="col-span-2 mt-2">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-t border-slate-100 pt-4">Account Security</h3>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Confirm Password</label>
                <input type="password" name="confirm_password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

             <!-- Photo -->
             <div class="col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Profile Photo query (Optional)</label>
                <input type="file" name="photo" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-xs text-slate-400 mt-1">Used for ID Card generation.</p>
            </div>
        </div>

        <div class="pt-6">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                Register Account
            </button>
        </div>
        
        <div class="text-center mt-4">
            <p class="text-sm text-slate-600">Already have an account? <a href="login.php" class="text-blue-600 font-bold hover:underline">Login here</a></p>
        </div>

    </form>
    <?php endif; ?>
</div>

</body>
</html>
