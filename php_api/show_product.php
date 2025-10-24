<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

// เชื่อมต่อฐานข้อมูล
include 'condb.php';

try {
    // ตรวจสอบคำขอที่ได้รับจาก Client ตามประเภทของคำว่าเป็น GET หรือ POST
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method == 'GET') {
        // ✅ ตรวจสอบว่ามีการส่ง category_id มาหรือไม่
        if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
            // กรองสินค้าตามหมวดหมู่
            $category_id = $_GET['category_id'];
            $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? ORDER BY product_name");
            $stmt->execute([$category_id]);
        } else {
            // แสดงสินค้าทั้งหมด
            $stmt = $conn->prepare("SELECT * FROM products ORDER BY product_name");
            $stmt->execute();
        }
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true, 
            "data" => $data,
            "count" => count($data)
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "Invalid request method"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false, 
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>