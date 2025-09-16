<?php
include 'db_connect.php';

// Real-time username check
if (isset($_GET['username_check'])) {
    $username = $_GET['username_check'];
    $exclude_id = isset($_GET['exclude_id']) ? intval($_GET['exclude_id']) : 0;
    $sql = "SELECT user_id FROM Users WHERE username = ?" . ($exclude_id ? " AND user_id != ?" : "");
    $stmt = $conn->prepare($sql);
    if ($exclude_id) {
        $stmt->bind_param("si", $username, $exclude_id);
    } else {
        $stmt->bind_param("s", $username);
    }
    $stmt->execute();
    $stmt->store_result();
    echo json_encode(['taken' => $stmt->num_rows > 0]);
    $stmt->close();
    $conn->close();
    exit;
}

// Real-time phone check
if (isset($_GET['phone_check'])) {
    $phone = $_GET['phone_check'];
    $exclude_id = isset($_GET['exclude_id']) ? intval($_GET['exclude_id']) : 0;
    $sql = "SELECT user_id FROM Users WHERE phone = ?" . ($exclude_id ? " AND user_id != ?" : "");
    $stmt = $conn->prepare($sql);
    if ($exclude_id) {
        $stmt->bind_param("si", $phone, $exclude_id);
    } else {
        $stmt->bind_param("s", $phone);
    }
    $stmt->execute();
    $stmt->store_result();
    echo json_encode(['taken' => $stmt->num_rows > 0]);
    $stmt->close();
    $conn->close();
    exit;
}

if (isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    $sql = "SELECT * FROM Users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode($user);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}

$conn->close();

