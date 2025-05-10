<?php
require_once '../config.php';

function calculateCorrelation($x, $y) {
    $n = count($x);
    if ($n < 2) return null;
    
    $sum_x = array_sum($x);
    $sum_y = array_sum($y);
    
    $sum_x_squared = array_sum(array_map(function($val) { return $val * $val; }, $x));
    $sum_y_squared = array_sum(array_map(function($val) { return $val * $val; }, $y));
    
    $sum_xy = array_sum(array_map(function($x_val, $y_val) { 
        return $x_val * $y_val; 
    }, $x, $y));
    
    $numerator = ($n * $sum_xy) - ($sum_x * $sum_y);
    $denominator = sqrt(($n * $sum_x_squared - ($sum_x * $sum_x)) * ($n * $sum_y_squared - ($sum_y * $sum_y)));
    
    if ($denominator == 0) return 0;
    return $numerator / $denominator;
}

$dataset_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$dataset_id) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Dataset ID required']);
    exit;
}

try {
    $table_name = "dataset_" . $dataset_id;
    
    // Get numeric columns
    $result = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_NAME = '$table_name' 
                           AND DATA_TYPE IN ('int', 'decimal', 'float', 'double')");
    
    $numeric_columns = [];
    while ($row = $result->fetch_assoc()) {
        $numeric_columns[] = $row['COLUMN_NAME'];
    }
    
    // Calculate correlations
    $correlations = [];
    foreach ($numeric_columns as $i => $col1) {
        $correlations[$i] = [];
        foreach ($numeric_columns as $j => $col2) {
            if ($i <= $j) {  // Only calculate for upper triangle (including diagonal)
                $result = $conn->query("SELECT `$col1`, `$col2` FROM $table_name 
                                      WHERE `$col1` IS NOT NULL AND `$col2` IS NOT NULL");
                
                $x = [];
                $y = [];
                while ($row = $result->fetch_assoc()) {
                    $x[] = (float)$row[$col1];
                    $y[] = (float)$row[$col2];
                }
                
                $correlation = calculateCorrelation($x, $y);
                $correlations[$i][$j] = $correlation;
                if ($i != $j) {  // Mirror the correlation for lower triangle
                    $correlations[$j][$i] = $correlation;
                }
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'columns' => $numeric_columns,
        'correlations' => $correlations
    ]);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
