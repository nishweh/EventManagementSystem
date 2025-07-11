<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null;
    $event_id = isset($_POST["event"]) ? $_POST["event"] : null;
    $rating = isset($_POST["rating"]) ? $_POST["rating"] : null;
    $comment = isset($_POST["comment"]) ? trim($_POST["comment"]) : '';

    // Semak semua medan wajib
    if (empty($user_id) || empty($event_id) || empty($rating) || empty($comment)) {
        echo "<script>alert('⚠️ Please fill all fields.'); window.history.back();</script>";
        exit;
    }

    try {
        // ✅ Use correct column names
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, event_id, feedback_rating, feedback_comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $event_id, $rating, $comment]);

        echo "<script>alert('✅ Thank you for your feedback!'); window.location.href = 'feedback_form.php';</script>";
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('❌ Error saving feedback: " . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }
} else {
    echo "<script>alert('❌ Invalid request.'); window.location.href = 'feedback_form.php';</script>";
    exit;
}
?>