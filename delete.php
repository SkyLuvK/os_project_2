<?php
session_start();
require_once 'config.php'; // Adjust the path if necessary

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
                echo "File deleted successfully.";
                header("Location: file_manager.php");
            } else {
                echo "Error deleting file.";
            }
            $stmt_delete->close();
        } else {
            echo "Error deleting file.";
        }
    } else {
        echo "File not found.";
    }
}
?>
delete.php