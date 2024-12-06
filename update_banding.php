<?php
session_start();
$config = parse_ini_file('db_config.ini');

$serverName = $config['serverName'];
$connectionInfo = array(
    "Database" => $config['database'],
    "UID" => $config['username'],
    "PWD" => $config['password']
);
$conn = sqlsrv_connect($serverName, $connectionInfo);

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_banding = $data['id_banding'];
$status = $data['status'];

$query = "UPDATE dbo.Banding SET kesepakatan = ? WHERE id_banding = ?";
$params = [$status, $id_banding];
$stmt = sqlsrv_query($conn, $query, $params);

if ($stmt) {
    echo json_encode(['success' => true, 'message' => $status ? 'Appeal accepted.' : 'Appeal rejected.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update appeal status.']);
}
exit;
?>
