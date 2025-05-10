<?php
require_once __DIR__ . '/../config.php';

try {
    global $conn;
    if (!$conn) {
        throw new Exception("Database connection not available");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get last 7 days of data
    $stmt = $pdo->query("
        SELECT 
            DATE(timestamp) as date,
            SUM(consumption) as total_consumption
        FROM consumption_data
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(timestamp)
        ORDER BY date ASC
    ");
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = [];
    $values = [];
    
    foreach ($data as $row) {
        $labels[] = date('M d', strtotime($row['date']));
        $values[] = round($row['total_consumption'], 2);
    }
    
    echo json_encode([
        'labels' => $labels,
        'values' => $values
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
