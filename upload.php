<?php
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // Validate inputs
    if (empty($_POST['dataset_name'])) {
        die("Dataset name is required.");
    }

    $datasetName = sanitizeInput($_POST['dataset_name']);
    $datasetDescription = isset($_POST['dataset_description']) ? sanitizeInput($_POST['dataset_description']) : '';
    
    // Check for upload errors
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        die("File upload error: " . $_FILES['csv_file']['error']);
    }
    
    // Check file type
    $fileType = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
    if ($fileType !== 'csv') {
        die("Only CSV files are allowed.");
    }

    // Check if file is actually CSV
    // $mime = mime_content_type($_FILES['csv_file']['tmp_name']);
    // if (!in_array($mime, ['text/csv', 'text/plain', 'application/csv', 'application/octet-stream'])) {
    //     die("Invalid file type. Please upload a CSV file.");
    // }
    
    // Create uploads directory if it doesn't exist
    if (!file_exists('uploads')) {
        if (!mkdir('uploads', 0777, true)) {
            die("Failed to create upload directory.");
        }
    }
    
    // Generate unique filename
    $uploadDir = 'uploads/';
    $filename = uniqid() . '_' . basename($_FILES['csv_file']['name']);
    $targetFile = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $targetFile)) {
        die("Failed to move uploaded file. Check directory permissions.");
    }
    
    // Parse CSV file
    if (($file = fopen($targetFile, 'r')) === false) {
        die("Failed to open uploaded file.");
    }
    
    // Get headers
    if (($headers = fgetcsv($file)) === false) {
        fclose($file);
        die("Failed to read CSV headers. File might be empty.");
    }
    
    // Sanitize headers
    $sanitizedHeaders = [];
    foreach ($headers as $header) {
        $sanitizedHeaders[] = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower(trim($header)));
    }
    
    // Check for duplicate headers
    if (count($sanitizedHeaders) !== count(array_unique($sanitizedHeaders))) {
        fclose($file);
        die("Duplicate column names found in CSV headers.");
    }
    
    // Create dataset record
    $uploadDate = date('Y-m-d H:i:s');
    $sql = "INSERT INTO datasets (name, description, filename, upload_date) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        fclose($file);
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("ssss", $datasetName, $datasetDescription, $filename, $uploadDate);
    
    if (!$stmt->execute()) {
        fclose($file);
        die("Error creating dataset record: " . $conn->error);
    }
    
    $dataset_id = $stmt->insert_id;
    $stmt->close();
    
    // Create table for this dataset
    $table_name = "dataset_" . $dataset_id;
    
    $createTableSql = "CREATE TABLE $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,";
    
    // Add columns based on headers
    foreach ($sanitizedHeaders as $header) {
        $createTableSql .= "`$header` TEXT,";
    }
    
    $createTableSql .= "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)";
    
    if (!$conn->query($createTableSql)) {
        // Clean up if table creation fails
        $conn->query("DELETE FROM datasets WHERE id = $dataset_id");
        unlink($targetFile); // Delete the uploaded file
        fclose($file);
        die("Error creating data table: " . $conn->error);
    }
    
    // Insert data
    $recordCount = 0;
    $batchSize = 100;
    $batchValues = [];
    
    while (($row = fgetcsv($file)) !== false) {
        if (count($row) !== count($sanitizedHeaders)) {
            // Skip malformed rows but continue processing
            continue;
        }
        
        $values = array_map(function($value) use ($conn) {
            return "'" . $conn->real_escape_string($value) . "'";
        }, $row);
        
        $batchValues[] = "(" . implode(', ', $values) . ")";
        
        // Insert in batches for better performance
        if (count($batchValues) >= $batchSize) {
            $insertSql = "INSERT INTO $table_name (" . implode(', ', $sanitizedHeaders) . ") 
                          VALUES " . implode(', ', $batchValues);
            
            if (!$conn->query($insertSql)) {
                // Log error but continue processing
                error_log("Error inserting batch: " . $conn->error);
            } else {
                $recordCount += count($batchValues);
            }
            
            $batchValues = [];
        }
    }
    
    // Insert any remaining records
    if (!empty($batchValues)) {
        $insertSql = "INSERT INTO $table_name (" . implode(', ', $sanitizedHeaders) . ") 
                      VALUES " . implode(', ', $batchValues);
        
        if (!$conn->query($insertSql)) {
            error_log("Error inserting final batch: " . $conn->error);
        } else {
            $recordCount += count($batchValues);
        }
    }
    
    fclose($file);
    
    // Update record count
    $conn->query("UPDATE datasets SET record_count = $recordCount WHERE id = $dataset_id");
    
    // Redirect to analysis page
    header("Location: analyze.php?id=$dataset_id");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>