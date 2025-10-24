<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include 'condb.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['username']) || !isset($data['password'])) {
    echo json_encode([
        "success" => false,
        "message" => "กรุณากรอกข้อมูลให้ครบถ้วน"
    ]);
    exit;
}

$username = trim($data['username']);
$password = trim($data['password']);

if (empty($username) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน"
    ]);
    exit;
}

try {
    // ดึงข้อมูลจากตาราง employees
    $stmt = $conn->prepare("SELECT employee_id, username, password, name, position FROM employees WHERE username = ?");
    $stmt->execute([$username]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($employee) {
        // ตรวจสอบรหัสผ่าน
        // ถ้าใช้ password_hash ให้ใช้ password_verify
        if (password_verify($password, $employee['password'])) {
            echo json_encode([
                "success" => true,
                "message" => "เข้าสู่ระบบสำเร็จ",
                "employee" => [
                    "id" => $employee['employee_id'],
                    "username" => $employee['username'],
                    "name" => $employee['name'],
                    "position" => $employee['position']
                ]
            ]);
        } 
        // ถ้าเก็บ password แบบ plain text (ไม่แนะนำ แต่สำหรับทดสอบ)
        else if ($password === $employee['password']) {
            echo json_encode([
                "success" => true,
                "message" => "เข้าสู่ระบบสำเร็จ",
                "employee" => [
                    "id" => $employee['employee_id'],
                    "username" => $employee['username'],
                    "name" => $employee['name'],
                    "position" => $employee['position']
                ]
            ]);
        }
        else {
            echo json_encode([
                "success" => false,
                "message" => "รหัสผ่านไม่ถูกต้อง"
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "ไม่พบชื่อผู้ใช้นี้ในระบบ"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล"
    ]);
    error_log("Database error: " . $e->getMessage());
}
?>