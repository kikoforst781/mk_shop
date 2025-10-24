<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

include 'condb.php';

$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    // เพิ่ม / แก้ไข / ลบ
    switch($action) {

        case 'add':
            $category_id = $_POST['category_id'] ?? null;
            $product_name = $_POST['product_name'];
            $description = $_POST['description'];
            $price = $_POST['price'];
            $stock = $_POST['stock'];

            // อัพโหลดไฟล์รูป
            $filename = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $targetDir = "uploads/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $filename = time() . '_' . basename($_FILES['image']['name']);
                $targetFile = $targetDir . $filename;
                move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
            }

            $sql = "INSERT INTO products (category_id, product_name, description, price, stock, image)
                    VALUES (:category_id, :product_name, :description, :price, :stock, :image)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':image', $filename);

            if ($stmt->execute()) {
                echo json_encode(["message" => "เพิ่มสินค้าสำเร็จ"]);
            } else {
                echo json_encode(["error" => "เพิ่มสินค้าล้มเหลว"]);
            }
            break;

        case 'update':
            $product_id = $_POST['product_id'];
            $category_id = $_POST['category_id'] ?? null;
            $product_name = $_POST['product_name'];
            $description = $_POST['description'];
            $price = $_POST['price'];
            $stock = $_POST['stock'];

            // อัพโหลดไฟล์รูป
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $targetDir = "uploads/";
                $filename = time() . '_' . basename($_FILES['image']['name']);
                $targetFile = $targetDir . $filename;
                move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
                $imageSQL = ", image = :image";
            } else {
                $imageSQL = "";
            }

            $sql = "UPDATE products SET 
                        category_id = :category_id,
                        product_name = :product_name,
                        description = :description,
                        price = :price,
                        stock = :stock
                        $imageSQL
                    WHERE product_id = :product_id";
            $stmt = $conn->prepare($sql);

            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':product_id', $product_id);
            if (isset($filename)) $stmt->bindParam(':image', $filename);

            if ($stmt->execute()) {
                echo json_encode(["message" => "แก้ไขสินค้าสำเร็จ"]);
            } else {
                echo json_encode(["error" => "แก้ไขสินค้าล้มเหลว"]);
            }
            break;

        case 'delete':
            $product_id = $_POST['product_id'];

            // 🔍 ดึงชื่อไฟล์รูปจากฐานข้อมูลก่อนลบ
            $stmt = $conn->prepare("SELECT image FROM products WHERE product_id = :product_id");
            $stmt->bindParam(':product_id', $product_id);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product && !empty($product['image'])) {
                $filePath = "uploads/" . $product['image'];
                // 🧹 ลบไฟล์รูปถ้ามีอยู่จริง
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // 🔥 ลบข้อมูลสินค้าออกจากฐานข้อมูล
            $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :product_id");
            $stmt->bindParam(':product_id', $product_id);

            if ($stmt->execute()) {
                echo json_encode(["message" => "ลบสินค้าสำเร็จ และลบรูปภาพออกจากโฟลเดอร์แล้ว"]);
            } else {
                echo json_encode(["error" => "ลบสินค้าล้มเหลว"]);
            }
            break;

        default:
            echo json_encode(["error" => "Action ไม่ถูกต้อง"]);
            break;
    }

} else {
    // GET: ดึงข้อมูลสินค้าพร้อมหมวดหมู่
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            c.category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        ORDER BY p.product_id DESC
    ");
    
    if ($stmt->execute()) {
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $products]);
    } else {
        echo json_encode(["success" => false, "data" => []]);
    }
}
?>