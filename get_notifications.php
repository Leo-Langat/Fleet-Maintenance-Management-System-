<?php
session_start();
require_once 'db_connect.php';
require_once 'create_notification.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get only unread notifications from database
$notifications = getUnreadNotifications($user_id);

// If no unread notifications in database, check for maintenance reminders and create them
if (empty($notifications) && $user_role === 'driver') {
    // Get the driver's vehicle
    $vehicle_query = "SELECT vehicle_id, registration_no FROM vehicles WHERE assigned_driver = ?";
    $stmt = $conn->prepare($vehicle_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle = $result->fetch_assoc();

    if ($vehicle) {
        // Get maintenance schedules within next 24 hours
        $query = "SELECT ms.schedule_id, ms.schedule_date, ms.schedule_start_time, ms.schedule_end_time,
                  mt.task_name, sc.service_center_name, v.registration_no
                  FROM maintenance_schedule ms
                  JOIN maintenance_tasks mt ON ms.task_id = mt.task_id
                  JOIN service_centers sc ON ms.service_center_id = sc.service_center_id
                  JOIN vehicles v ON ms.vehicle_id = v.vehicle_id
                  WHERE ms.vehicle_id = ? 
                  AND ms.status = 'Scheduled'
                  AND ms.schedule_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                  ORDER BY ms.schedule_date ASC, ms.schedule_start_time ASC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $vehicle['vehicle_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Check if notification already exists for this schedule (read or unread)
            $check_query = "SELECT notification_id FROM Notifications WHERE user_id = ? AND type = 'maintenance' AND related_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("ii", $user_id, $row['schedule_id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            // Only create notification if it doesn't already exist
            if ($check_result->num_rows === 0) {
                $title = "Maintenance Reminder";
                $message = "Your vehicle ({$row['registration_no']}) is scheduled for {$row['task_name']} at {$row['service_center_name']} on " . 
                          date('M d, Y', strtotime($row['schedule_date'])) . " at " . 
                          date('h:i A', strtotime($row['schedule_start_time']));
                
                createNotification($user_id, $title, $message, 'maintenance', $row['schedule_id']);
            }
        }
        
        // Get the newly created unread notifications
        $notifications = getUnreadNotifications($user_id);
    }
}

// If no unread notifications in database, check for maintenance reminders for mechanics
if (empty($notifications) && $user_role === 'mechanic') {
    // Get the mechanic's service center
    $service_center_query = "SELECT sc.service_center_id, sc.service_center_name 
                            FROM Service_Center_Mechanics scm 
                            JOIN Service_Centers sc ON scm.service_center_id = sc.service_center_id 
                            WHERE scm.mechanic_id = ?";
    $stmt = $conn->prepare($service_center_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service_center = $result->fetch_assoc();

    if ($service_center) {
        // Get maintenance schedules within next 24 hours for the mechanic's service center
        $query = "SELECT ms.schedule_id, ms.schedule_date, ms.schedule_start_time, ms.schedule_end_time,
                  mt.task_name, sc.service_center_name, v.registration_no
                  FROM maintenance_schedule ms
                  JOIN maintenance_tasks mt ON ms.task_id = mt.task_id
                  JOIN service_centers sc ON ms.service_center_id = sc.service_center_id
                  JOIN vehicles v ON ms.vehicle_id = v.vehicle_id
                  WHERE ms.service_center_id = ? 
                  AND ms.status = 'Scheduled'
                  AND ms.schedule_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                  ORDER BY ms.schedule_date ASC, ms.schedule_start_time ASC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $service_center['service_center_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Check if notification already exists for this schedule (read or unread)
            $check_query = "SELECT notification_id FROM Notifications WHERE user_id = ? AND type = 'maintenance' AND related_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("ii", $user_id, $row['schedule_id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            // Only create notification if it doesn't already exist
            if ($check_result->num_rows === 0) {
                $title = "Maintenance Job Reminder";
                $message = "Vehicle {$row['registration_no']} is scheduled for {$row['task_name']} on " . 
                          date('M d, Y', strtotime($row['schedule_date'])) . " at " . 
                          date('h:i A', strtotime($row['schedule_start_time']));
                
                createNotification($user_id, $title, $message, 'maintenance', $row['schedule_id']);
            }
        }
        
        // Get the newly created unread notifications
        $notifications = getUnreadNotifications($user_id);
    }
}

// Format notifications for frontend
$formatted_notifications = [];
foreach ($notifications as $notification) {
    $formatted_notifications[] = [
        'id' => $notification['notification_id'],
        'title' => $notification['title'],
        'message' => $notification['message'],
        'type' => $notification['type'],
        'related_id' => $notification['related_id'],
        'created_at' => date('M d, Y H:i', strtotime($notification['created_at'])),
        'is_read' => (bool)$notification['is_read']
    ];
}

echo json_encode(['notifications' => $formatted_notifications]);
?> 