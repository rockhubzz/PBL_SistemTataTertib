<?php
// Start session
session_start();

// Include database configuration
$config = parse_ini_file('db_config.ini');

// Extract connection details
$serverName = $config['serverName'];
$connectionInfo = array(
    "Database" => $config['database'],
    "UID" => $config['username'],
    "PWD" => $config['password']
);
$conn = sqlsrv_connect($serverName, $connectionInfo);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

// Query to fetch mahasiswa data
$query = "
    SELECT 
        m.nim, 
        u.nama AS nama, 
        m.tingkat_pelanggaran_max 
    FROM 
        dbo.Mahasiswa m
    INNER JOIN 
        dbo.Users u 
    ON 
        m.user_id = u.user_id
";
$stmt = sqlsrv_query($conn, $query);

if (!$stmt) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of Mahasiswas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>List of Mahasiswas</h1>
    <table>
        <thead>
            <tr>
                <th>NIM</th>
                <th>Nama</th>
                <th>Tingkat Pelanggaran Max</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nim']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['tingkat_pelanggaran_max']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
