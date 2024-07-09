<?php
session_start();
require_once 'config.php'; // Adjust the path if necessary

// Database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// Initialize variables for messages
$uploadMessage = '';
$deleteMessage = '';

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload'])) {
    $target_dir = "files/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file already exists
    if (file_exists($target_file)) {
        $uploadMessage = '<span class="text-danger">File already exists.</span>';
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 500000) {
        $uploadMessage = '<span class="text-danger"> Your file is too large.</span>';
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $uploadMessage .= '<span class="text-danger"> Your file was not uploaded.</span>';
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            // Insert file details into user_files table
            $filename = basename($_FILES["fileToUpload"]["name"]);
            $user_id = $_SESSION['id'];
            $filepath = $target_file;

            $sql = "INSERT INTO user_files (user_id, filename, filepath) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $user_id, $filename, $filepath);
            if ($stmt->execute()) {
                $uploadMessage .= '<span class="text-success">The file ' . htmlspecialchars($filename) . ' has been uploaded.</span>';
            } else {
                $uploadMessage .= '<span class="text-danger">Error uploading file.</span>';
            }
            $stmt->close();
        } else {
            $uploadMessage .= '<span class="text-danger">There was an error uploading your file.</span>';
        }
    }
}

// Handle file deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $file_id = $_POST['file_id'];

    // Fetch filepath based on file_id and user_id
    $sql = "SELECT filepath FROM user_files WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $file_id, $_SESSION['id']);
    $stmt->execute();
    $stmt->bind_result($filepath);
    $stmt->fetch();
    $stmt->close();

    // Delete file if exists and belongs to the user
    if (isset($filepath)) {
        if (unlink($filepath)) {
            // Delete record from database
            $sql_delete = "DELETE FROM user_files WHERE id = ? AND user_id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("ii", $file_id, $_SESSION['id']);
            if ($stmt_delete->execute()) {
                $deleteMessage .= '<span class="text-success">File have been deleted successfully.</span>';
            } else {
                $deleteMessage .= '<span class="text-danger">Error deleting the file.</span>';
            }
            $stmt_delete->close();
        } else {
            $deleteMessage .= '<span class="text-danger">Error deleting the file.</span>';
        }
    } else {
        $deleteMessage .= '<span class="text-danger">File was not found.</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Files Management System</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <style>
        body {
            background-color: #eef2f5;
            font-family: 'Arial', sans-serif;
            color: #333;
            padding-top: 80px; /* Adjust padding top to accommodate fixed header */
        }
        .file-list {
            margin-top: 20px;
        }
        .file-list table {
            width: 100%;
            border-collapse: collapse;
        }
        .file-list th, .file-list td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .file-list th {
            background-color: #007bff;
            color: #fff;
        }
        .file-list tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        /* Fixed header styles */
        .fixed-top {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            background-color: #5a9bd4; /* Changed to a more appealing color */
            border-bottom: 2px solid #3b73a2; /* Solid border at the bottom */
            color: black;
            font-weight: bold;
        }
        .fixed-top h2 {
            margin: 0;
            padding: 15px;
        }
        .username {
            font-size: 23px;
            color: #fff;
            margin-top: 5px;
        }
        .header-padding {
            padding-top: 80px; /* Padding to adjust content below fixed header */
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .btn-primary {
            background-color: #5a9bd4;
            border-color: #5a9bd4;
        }
        .btn-primary:hover {
            background-color: #3b73a2;
            border-color: #3b73a2;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .modal-content {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-header {
            border-bottom: none;
            background-color: #5a9bd4;
            color: #fff;
            padding: 20px;
        }
        .modal-title {
            margin: 0;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .close {
            color: #fff;
            opacity: 1;
            font-size: 1.5rem;
        }
        .modal-body {
            padding: 20px;
            background-color: #f9f9f9;
        }
        .form-label {
            font-weight: bold;
        }
        .form-control {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 1rem;
        }
        .btn-success {
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 1rem;
        }
        footer {
            background-color: #5a9bd4;
            color: #fff;
            padding: 15px;
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="fixed-top">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="mb-0">Personal Files Access System</h2>
                    <?php if (isset($_SESSION['username'])): ?>
                        <div class="username">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-auto">
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mt-4 header-padding">
        <!-- File upload form -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="fileToUpload" class="form-label">Select a file to upload:</label>
            </div>
            <div class="col-auto">
                <input type="file" name="fileToUpload" id="fileToUpload" class="form-control" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-success" name="upload">Upload</button>
            </div>
        </form>

        <!-- Upload message -->
        <?php if (!empty($uploadMessage)): ?>
            <div class="row mt-2">
                <div class="col-auto error-message"><?php echo $uploadMessage; ?></div>
            </div>
        <?php endif; ?>

        <!-- Uploaded files list -->
        <h3 class="mt-4">Uploaded Files:</h3>
        <div class="file-list">
            <table>
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch and display user's uploaded files
                    $sql_files = "SELECT id, filename FROM user_files WHERE user_id = ?";
                    $stmt_files = $conn->prepare($sql_files);
                    $stmt_files->bind_param("i", $_SESSION['id']);
                    $stmt_files->execute();
                    $stmt_files->bind_result($file_id, $filename);

                    while ($stmt_files->fetch()) {
                        echo '<tr>
                            <td>' . htmlspecialchars($filename) . '</td>
                            <td>
                                <form class="d-inline">
                                    <input type="hidden" name="file_id" value="' . $file_id . '">
                                    <button type="button" class="btn btn-warning download-btn" data-filename="' . htmlspecialchars($filename) . '">Access</button>
                                </form>
                                <form action="delete.php" method="post" class="d-inline">
                                    <input type="hidden" name="file_id" value="' . $file_id . '">
                                    <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>';
                    }
                    $stmt_files->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        &copy; 2024 Personal Files Management System
    </footer>

    <!-- OTP Modal -->
    <div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="otpModalLabel">Verification of OTP</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="otpForm">
                        <div class="mb-3">
                            <label for="otp" class="form-label">Enter Yout OTP:</label>
                            <input type="text" class="form-control" id="otp" name="otp" required>
                        </div>
                        <button type="submit" class="btn btn-success">Verify</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function () {
            var downloadFile;

            $('.download-btn').on('click', function() {
                downloadFile = $(this).data('filename');
                // Send user ID along with POST request to send_otp.php
                $.post('send_otp.php', { user_id: <?php echo json_encode($_SESSION['id']); ?> }, function(response) {
                    console.log('Response from send_otp.php:', response);
                    $('#otpModal').modal('show');
                }).fail(function() {
                    console.error('Failed to send OTP.');
                });
            });

            $('#otpForm').on('submit', function (e) {
                e.preventDefault();
                var otp = $('#otp').val();

                // Verify OTP using AJAX
                $.post('verify_otp.php', { otp: otp, filename: downloadFile }, function (response) {
                    if (response === 'verified') {
                        var downloadUrl = 'files/' + downloadFile;
                        var link = document.createElement('a');
                        link.href = downloadUrl;
                        link.download = downloadFile;
                        link.click();
                        $('#otpModal').modal('hide');
                    } else {
                        alert('Invalid OTP. Please try again.');
                    }
                }).fail(function() {
                    alert('Failed to verify OTP. Please try again.');
                });
            });
        });
    </script>
</body>
</html>