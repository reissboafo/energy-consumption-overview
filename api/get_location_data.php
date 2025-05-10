<?php
require_once '../config.php';

function getZipCodeLocation($zipcode) {
    try {
        // Clean up zipcode - remove any non-alphanumeric characters
        $zipcode = preg_replace('/[^a-zA-Z0-9]/', '', $zipcode);
        
        // Cache mechanism to avoid repeated API calls
        $cacheFile = '../cache/' . md5($zipcode) . '.json';
        if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 86400) {
            return json_decode(file_get_contents($cacheFile), true);
        }

        $url = "https://api.zippopotam.us/us/" . urlencode($zipcode);
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'Energy Dashboard/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['places'][0])) {
                $location = [
                    'lat' => (float)$data['places'][0]['latitude'],
                    'lon' => (float)$data['places'][0]['longitude'],
                    'city' => $data['places'][0]['place name'],
                    'state' => $data['places'][0]['state']
                ];
                
                // Cache the result
                if (!is_dir('../cache')) {
                    mkdir('../cache', 0777, true);
                }
                file_put_contents($cacheFile, json_encode($location));
                
                return $location;
            }
        }
        return null;
    } catch (Exception $e) {
        error_log("Error getting location for zipcode {$zipcode}: " . $e->getMessage());
        return null;
    }
}

$dataset_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$dataset_id) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Dataset ID required']);
    exit;
}

$table_name = "dataset_" . $dataset_id;

// Get the location columns
$result = $conn->query("SHOW COLUMNS FROM {$table_name}");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

$zipcode_cols = array_filter($columns, function($col) {
    return strpos(strtolower($col), 'zip') !== false || strpos(strtolower($col), 'postal') !== false;
});

$city_cols = array_filter($columns, function($col) {
    return strpos(strtolower($col), 'city') !== false;
});

$locations = [];
$processed_locations = [];

if (!empty($zipcode_cols)) {
    $zip_col = reset($zipcode_cols);
    $query = "SELECT DISTINCT `{$zip_col}` as zipcode FROM {$table_name} WHERE `{$zip_col}` IS NOT NULL LIMIT 100";
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $zipcode = trim($row['zipcode']);
        if (!empty($zipcode) && !isset($processed_locations[$zipcode])) {
            $location = getZipCodeLocation($zipcode);
            if ($location) {
                $locations[] = array_merge(['zipcode' => $zipcode], $location);
                $processed_locations[$zipcode] = true;
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['locations' => $locations]);
