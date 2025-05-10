<?php
require_once '../config.php';

$dataset_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    if (!$dataset_id) {
        // Get latest dataset if no ID provided
        $result = $conn->query("SELECT id FROM datasets ORDER BY upload_date DESC LIMIT 1");
        $dataset_id = $result->fetch_assoc()['id'];
    }    $table_name = "dataset_" . $dataset_id;
    
    // Get data points grouped by zipcode
    $query = "SELECT 
        zipcode_from,
        annual_consume,
        annual_consume_lowtarif_perc
        FROM {$table_name}
        WHERE annual_consume IS NOT NULL 
        AND annual_consume_lowtarif_perc IS NOT NULL
        ORDER BY annual_consume DESC";    $result = $conn->query($query);
    
    $data = [
        'labels' => [],
        'consumption' => [],
        'lowTariffPerc' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['zipcode_from'];
        $data['consumption'][] = (float)$row['annual_consume'];
        $data['lowTariffPerc'][] = (float)$row['annual_consume_lowtarif_perc'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
