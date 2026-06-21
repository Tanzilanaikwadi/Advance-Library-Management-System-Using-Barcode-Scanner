<?php
require_once 'config/db.php';
include 'header.php';

// Include the simple barcode library
if (!class_exists('SimpleBarcode')) {
    include_once 'lib_barcode.php';
}

$message = '';
$error = '';
$generated_barcode = '';
$download_link_front = '';
$download_link_back = '';
$new_member_barcode = '';

// Check if we need to generate a new barcode
if (isset($_GET['generate']) && $_GET['generate'] == 'true') {
    $generated_barcode = 'STU' . rand(100000, 999999);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = trim($_POST['barcode']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $department = trim($_POST['department']);
    $admission_year = trim($_POST['admission_year']);
    $prn_number = trim($_POST['prn_number']);

    // Photo upload handling
    $photo_path = '';
    $upload_dir = 'uploads/members/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_name = basename($_FILES['photo']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_exts)) {
            $new_file_name = $barcode . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            if (move_uploaded_file($file_tmp, $destination)) {
                $photo_path = $destination;
            } else {
                $error = "Failed to upload photo.";
            }
        } else {
            $error = "Invalid file type. Only JPG and PNG allowed.";
        }
    }

    if (empty($barcode)) {
        $error = "Barcode / ID is required.";
    } elseif (empty($error)) {
        try {
            // Check existence logic skipped for brevity, rely on DB unique constraint
            $stmt = $pdo->prepare("INSERT INTO members (barcode_id, name, email, phone, address, department, admission_year, prn_number, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$barcode, $name, $email, $phone, $address, $department, $admission_year, $prn_number, $photo_path]);

            // Generate ID Cards
            $cards = generateIDCards($barcode, $name, $department, $admission_year, $prn_number, $photo_path, $address, $phone);

            $id_card_front = $cards['front'];

            // Update member with ID card path
            if ($id_card_front) {
                $stmt = $pdo->prepare("UPDATE members SET id_card_path = ? WHERE barcode_id = ?");
                $stmt->execute([$id_card_front, $barcode]);
            }

            $download_link_front = $cards['front'];
            $download_link_back = $cards['back'];

            $message = "Member added successfully!";
            $new_member_barcode = $barcode;
            $generated_barcode = '';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Error: Member with this Barcode ID already exists.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

function generateIDCards($barcode, $name, $dept, $year, $prn, $photo_path, $address, $phone)
{
    // --- FRONT SIDE ---
    $width = 600;
    $height = 380;
    $img = imagecreatetruecolor($width, $height);

    // Colors
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $blue = imagecolorallocate($img, 37, 99, 235); // Key Theme Color
    $dark_blue = imagecolorallocate($img, 30, 64, 175);
    $gray = imagecolorallocate($img, 71, 85, 105);
    $light_bg = imagecolorallocate($img, 248, 250, 252);

    imagefilledrectangle($img, 0, 0, $width, $height, $light_bg);

    // Header
    imagefilledrectangle($img, 0, 0, $width, 90, $blue);
    imagerectangle($img, 10, 10, $width - 10, $height - 10, $blue); // Border

    // College Name / Title
    $font_size = 5;
    imagestring($img, $font_size, 150, 25, "UNIVERSITY LIBRARY", $white);
    imagestring($img, 4, 180, 50, "STUDENT IDENTITY CARD", $white);

    // Photo
    if ($photo_path && file_exists($photo_path)) {
        $ext = strtolower(pathinfo($photo_path, PATHINFO_EXTENSION));
        if ($ext == 'png') {
            $src = imagecreatefrompng($photo_path);
        } else {
            $src = imagecreatefromjpeg($photo_path);
        }

        if ($src) {
            $photo_w = 110;
            $photo_h = 130;
            imagecopyresampled($img, $src, 25, 110, 0, 0, $photo_w, $photo_h, imagesx($src), imagesy($src));
            imagedestroy($src);
            imagerectangle($img, 24, 109, 25 + $photo_w, 110 + $photo_h, $black);
        }
    } else {
        imagerectangle($img, 25, 110, 135, 240, $black);
        imagestring($img, 3, 50, 160, "No Photo", $black);
    }

    // Details
    $text_x = 160;
    $start_y = 110;
    $lh = 28;

    imagestring($img, 5, $text_x, $start_y, strtoupper($name), $black);

    // Labels and Values
    imagestring($img, 4, $text_x, $start_y + $lh * 1.5, "ID No   : " . $barcode, $black);
    imagestring($img, 4, $text_x, $start_y + $lh * 2.5, "Dept    : " . $dept, $black);
    imagestring($img, 4, $text_x, $start_y + $lh * 3.5, "Year    : " . $year, $black);
    imagestring($img, 4, $text_x, $start_y + $lh * 4.5, "PRN     : " . $prn, $black);

    $valid_upto = (intval($year) > 0) ? (intval($year) + 4) : date('Y') + 1;
    imagestring($img, 4, $text_x, $start_y + $lh * 5.5, "Valid Upto: " . $valid_upto, $dark_blue);

    // Barcode Generation at Bottom
    // Use Code128 logic directly manually drawing lines
    if (class_exists('SimpleBarcode')) {
        // Center the barcode
        // Approx width of barcode
        $bc_x = 220;
        $bc_y = $height - 60;
        SimpleBarcode::draw($img, $bc_x, $bc_y, $barcode, $black, 40, 2);

        // Text below barcode
        imagestring($img, 4, $bc_x + 30, $bc_y + 42, $barcode, $black);
    } else {
        imagestring($img, 3, 200, $height - 30, "* " . $barcode . " *", $black);
    }

    $save_dir = 'uploads/id_cards/';
    if (!is_dir($save_dir)) mkdir($save_dir, 0777, true);

    $filename_front = 'ID_FRONT_' . $barcode . '.png';
    $filepath_front = $save_dir . $filename_front;
    imagepng($img, $filepath_front);
    imagedestroy($img);

    // --- BACK SIDE ---
    $img_back = imagecreatetruecolor($width, $height);
    imagefilledrectangle($img_back, 0, 0, $width, $height, $white);

    // Border
    imagerectangle($img_back, 10, 10, $width - 10, $height - 10, $blue);

    // Header
    imagefilledrectangle($img_back, 0, 0, $width, 50, $blue);
    imagestring($img_back, 4, 30, 15, "TERMS AND CONDITIONS", $white);

    // Content
    $rules = [
        "1. This card is non-transferable.",
        "2. This card must be presented while borrowing books.",
        "3. Loss of this card must be reported immediately.",
        "4. Card is valid only for the duration of the course.",
        "5. Please return to library if found."
    ];

    $y_rule = 80;
    foreach ($rules as $rule) {
        imagestring($img_back, 4, 30, $y_rule, $rule, $black);
        $y_rule += 30;
    }

    // Address / Contact
    $y_rule += 20;
    imagestring($img_back, 4, 30, $y_rule, "Address:", $dark_blue);
    imagestring($img_back, 3, 30, $y_rule + 20, "University Library Main Campus,", $gray);
    imagestring($img_back, 3, 30, $y_rule + 35, "123 Education Lane, City - 400001", $gray);
    imagestring($img_back, 3, 30, $y_rule + 50, "Ph: " . ($phone ? $phone : "0123-456789"), $gray);

    // Signature
    imagestring($img_back, 4, $width - 200, $height - 60, "Authorized Signatory", $black);
    imageline($img_back, $width - 200, $height - 70, $width - 40, $height - 70, $black);

    $filename_back = 'ID_BACK_' . $barcode . '.png';
    $filepath_back = $save_dir . $filename_back;
    imagepng($img_back, $filepath_back);
    imagedestroy($img_back);

    return ['front' => $filepath_front, 'back' => $filepath_back];
}

?>

<!-- Include JsBarcode Library for visual feedback -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>

<div class="page-header flex flex-col sm:flex-row justify-between sm:items-center items-start gap-4 mb-6">
    <div>
        <h1 class="page-title text-2xl font-bold text-slate-800">Register New Member</h1>
        <p class="text-slate-500 mt-1">Add a new student or faculty member and generate their ID card.</p>
    </div>
    <a href="export.php?type=members" class="btn bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-lg font-medium transition-colors flex items-center gap-2">
        <i class="fas fa-file-excel"></i> Export All Members
    </a>
</div>

<?php if ($message): ?>
    <div class="bg-emerald-50 text-emerald-800 p-5 rounded-xl mb-6 border border-emerald-500">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <strong class="text-base"><?php echo $message; ?></strong>
                <div class="mt-1">Barcode ID: <?php echo htmlspecialchars($new_member_barcode ?? ''); ?></div>
            </div>
            <div class="text-right w-full sm:w-auto overflow-x-auto bg-white p-2 rounded-lg flex justify-center">
                <svg id="barcode-display"></svg>
            </div>
        </div>

        <?php if ($download_link_front && $download_link_back): ?>
            <div class="mt-5 pt-5 border-t border-emerald-200 flex flex-col md:flex-row gap-5 justify-center">
                <!-- Front Card Preview & Download -->
                <div class="text-center w-full md:w-1/2 flex flex-col items-center">
                    <div class="mb-2 font-bold text-emerald-800">Front Side</div>
                    <img src="<?php echo $download_link_front; ?>" class="w-full max-w-[300px] border border-slate-300 rounded-lg shadow-sm">
                    <br>
                    <a href="<?php echo $download_link_front; ?>" download="ID_FRONT_<?php echo $new_member_barcode; ?>.png"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg no-underline inline-flex items-center gap-2 transition-colors">
                        <i class="fas fa-download"></i> Download Front
                    </a>
                </div>

                <!-- Back Card Preview & Download -->
                <div class="text-center w-full md:w-1/2 flex flex-col items-center">
                    <div class="mb-2 font-bold text-emerald-800">Back Side</div>
                    <img src="<?php echo $download_link_back; ?>" class="w-full max-w-[300px] border border-slate-300 rounded-lg shadow-sm">
                    <br>
                    <a href="<?php echo $download_link_back; ?>" download="ID_BACK_<?php echo $new_member_barcode; ?>.png"
                        class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg no-underline inline-flex items-center gap-2 transition-colors">
                        <i class="fas fa-download"></i> Download Back
                    </a>
                </div>
            </div>

            <script>
                // Auto trigger download for front side at least
                setTimeout(function() {
                    const link = document.createElement('a');
                    link.href = '<?php echo $download_link_front; ?>';
                    link.download = 'ID_FRONT_<?php echo $new_member_barcode; ?>.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }, 1000);
            </script>
        <?php endif; ?>
    </div>
    <script>
        JsBarcode("#barcode-display", "<?php echo $new_member_barcode; ?>", {
            format: "CODE128",
            width: 2,
            height: 40,
            displayValue: true
        });
    </script>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card max-w-4xl mx-auto p-4 sm:p-6 bg-white shadow-sm rounded-lg">
    <form method="POST" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="mb-4 text-left">
                <label class="block font-medium mb-2">Barcode / Student ID <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <input type="text" name="barcode" id="barcodeInput" value="<?php echo htmlspecialchars($generated_barcode); ?>" required
                        class="flex-1 p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 w-full sm:w-auto">
                    <button type="button" onclick="generateBarcode()" class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2.5 rounded-lg transition-colors whitespace-nowrap text-sm font-medium">Generate</button>
                </div>
                <small class="text-slate-500 block mt-1">Scan existing ID or generate new unique ID</small>
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Department</label>
                <input type="text" name="department" class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Admission Year</label>
                <input type="text" name="admission_year" class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">PRN Number</label>
                <input type="text" name="prn_number" class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Photo (Passport Size)</label>
                <input type="file" name="photo" accept="image/*" class="w-full p-2 border border-slate-300 rounded-lg bg-white outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Email</label>
                <input type="email" name="email" class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Phone</label>
                <input type="text" name="phone" class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="md:col-span-2 mb-4">
                <label class="block font-medium mb-2">Address</label>
                <textarea name="address" rows="3" class="w-full p-2.5 border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-medium transition-colors">Register Member</button>
        </div>
    </form>
</div>

<script>
    function generateBarcode() {
        const prefix = "STU";
        const random = Math.floor(100000 + Math.random() * 900000);
        document.getElementById('barcodeInput').value = `${prefix}${random}`;
    }
</script>

<?php include 'footer.php'; ?>