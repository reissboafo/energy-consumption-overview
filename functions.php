<?php
require_once 'config.php';

// Function to sanitize input data
function sanitizeInput($data) {
    global $conn;
    return htmlspecialchars(strip_tags($conn->real_escape_string(trim($data))));
}

// Function to get all uploaded datasets
function getDatasets() {
    global $conn;
    $datasets = [];
    
    $sql = "SELECT * FROM datasets ORDER BY upload_date DESC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $datasets[] = $row;
        }
    }
    
    return $datasets;
}

// Function to get dataset by ID
function getDataset($id) {
    global $conn;
    
    $id = (int)$id;
    $sql = "SELECT * FROM datasets WHERE id = $id";
    $result = $conn->query($sql);
    
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

// Function to get data for a specific dataset
function getDatasetData($dataset_id, $limit = 1000) {
    global $conn;
    
    $dataset_id = (int)$dataset_id;
    $limit = (int)$limit;
    
    // First get the table name
    $dataset = getDataset($dataset_id);
    if (!$dataset) return [];
    
    $table_name = "dataset_" . $dataset_id;
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($result->num_rows == 0) return [];
    
    // Get data
    $sql = "SELECT * FROM $table_name LIMIT $limit";
    $result = $conn->query($sql);
    
    $data = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Function to analyze dataset
function analyzeDataset($dataset_id) {
    global $conn;
    
    $dataset = getDataset($dataset_id);
    if (!$dataset) return null;
    
    $table_name = "dataset_" . $dataset_id;
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($result->num_rows == 0) return null;
    
    // Get column information with improved type detection
    $columns = [];
    $columnTypes = [];
    $result = $conn->query("SHOW COLUMNS FROM $table_name");
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        $columnTypes[$row['Field']] = [
            'type' => $row['Type'],
            'category' => determineColumnCategory($row['Field'], $row['Type'])
        ];
    }
    
    // Basic statistics for numeric columns with improved analysis
    $stats = [];
    foreach ($columns as $col) {
        $type = $columnTypes[$col]['type'];
        $category = $columnTypes[$col]['category'];
        
        if ($category === 'numeric') {
            $stats[$col] = getNumericStats($table_name, $col);
        }
    }
    
    // Time-based analysis with multi-series support
    $timeAnalysis = getTimeAnalysis($table_name, $columns, $columnTypes);
    
    return [
        'columns' => $columns,
        'column_types' => $columnTypes,
        'stats' => $stats,
        'time_analysis' => $timeAnalysis,
        'dataset_info' => $dataset
    ];
}

function determineColumnCategory($name, $type) {
    $name = strtolower($name);
    
    // Check if it's a timestamp/date column
    if (strpos($type, 'timestamp') !== false || 
        strpos($type, 'date') !== false || 
        strpos($name, 'date') !== false || 
        strpos($name, 'time') !== false) {
        return 'timestamp';
    }
    
    // Check if it's a location column
    if (strpos($name, 'zip') !== false || 
        strpos($name, 'postal') !== false ||
        strpos($name, 'city') !== false ||
        strpos($name, 'state') !== false ||
        strpos($name, 'country') !== false) {
        return 'location';
    }
    
    // Check if it's a numeric column
    if (strpos($type, 'int') !== false || 
        strpos($type, 'decimal') !== false || 
        strpos($type, 'float') !== false || 
        strpos($type, 'double') !== false) {
        return 'numeric';
    }
    
    return 'text';
}

function getNumericStats($table_name, $col) {
    global $conn;
    
    $stats = [
        'min' => 0,
        'max' => 0,
        'avg' => 0,
        'sum' => 0,
        'median' => 0,
        'stddev' => 0
    ];
    
    $result = $conn->query("SELECT 
        MIN(`$col`) as min_val,
        MAX(`$col`) as max_val,
        AVG(`$col`) as avg_val,
        SUM(`$col`) as sum_val,
        STD(`$col`) as std_val
        FROM $table_name
        WHERE `$col` IS NOT NULL");
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stats['min'] = $row['min_val'];
        $stats['max'] = $row['max_val'];
        $stats['avg'] = $row['avg_val'];
        $stats['sum'] = $row['sum_val'];
        $stats['stddev'] = $row['std_val'];
        
        // Approximate median using percentile
        $medianResult = $conn->query("SELECT `$col` as median_val
            FROM (
                SELECT `$col`, @rownum:=@rownum+1 as `row_number`, @total_rows:=@rownum
                FROM $table_name, (SELECT @rownum:=0) r
                WHERE `$col` IS NOT NULL
                ORDER BY `$col`
            ) as dd
            WHERE dd.row_number IN (FLOOR((@total_rows+1)/2), FLOOR((@total_rows+2)/2))");
        
        if ($medianResult->num_rows > 0) {
            $medianValues = [];
            while ($row = $medianResult->fetch_assoc()) {
                $medianValues[] = $row['median_val'];
            }
            $stats['median'] = array_sum($medianValues) / count($medianValues);
        }
    }
    
    return $stats;
}

function getTimeAnalysis($table_name, $columns, $columnTypes) {
    global $conn;
    
    $timeAnalysis = [];
    
    // Find timestamp columns
    $timestampCols = array_filter($columns, function($col) use ($columnTypes) {
        return $columnTypes[$col]['category'] === 'timestamp';
    });
    
    // Find numeric columns that might represent energy consumption
    $numericCols = array_filter($columns, function($col) use ($columnTypes) {
        return $columnTypes[$col]['category'] === 'numeric';
    });
    
    if (!empty($timestampCols) && !empty($numericCols)) {
        $timeCol = reset($timestampCols);
        
        foreach ($numericCols as $numericCol) {
            // Hourly analysis
            $result = $conn->query("SELECT 
                HOUR(`$timeCol`) as hour,
                AVG(`$numericCol`) as avg_value,
                MIN(`$numericCol`) as min_value,
                MAX(`$numericCol`) as max_value
                FROM $table_name
                WHERE `$timeCol` IS NOT NULL
                GROUP BY HOUR(`$timeCol`)
                ORDER BY hour");
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $timeAnalysis['hourly'][$numericCol][] = [
                        'hour' => (int)$row['hour'],
                        'avg' => (float)$row['avg_value'],
                        'min' => (float)$row['min_value'],
                        'max' => (float)$row['max_value']
                    ];
                }
            }
            
            // Daily analysis
            $result = $conn->query("SELECT 
                DATE(`$timeCol`) as date,
                AVG(`$numericCol`) as avg_value,
                MIN(`$numericCol`) as min_value,
                MAX(`$numericCol`) as max_value
                FROM $table_name
                WHERE `$timeCol` IS NOT NULL
                GROUP BY DATE(`$timeCol`)
                ORDER BY date");
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $timeAnalysis['daily'][$numericCol][] = [
                        'date' => $row['date'],
                        'avg' => (float)$row['avg_value'],
                        'min' => (float)$row['min_value'],
                        'max' => (float)$row['max_value']
                    ];
                }
            }
        }
    }
    
    return $timeAnalysis;
}
?>