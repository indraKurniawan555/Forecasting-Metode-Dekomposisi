<?php include 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard - Forecasting</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Dashboard</h1>

    <h2>Ringkasan Data</h2>
    <?php
    $result = $conn->query("SELECT COUNT(*) AS total_visitors, SUM(pengunjung) AS total_pengunjung FROM tourism_data");
    $data = $result->fetch_assoc();
    echo "<p>Total Records: " . $data['total_visitors'] . "</p>";
    echo "<p>Total Visitors: " . $data['total_pengunjung'] . "</p>";
    ?>

    <h2>Grafik Tren</h2>
    <canvas id="trendChart"></canvas>
    <script>
        const ctx = document.getElementById('trendChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php
                    $labels = $conn->query("SELECT DISTINCT tahun FROM tourism_data ORDER BY tahun");
                    while ($row = $labels->fetch_assoc()) {
                        echo "'" . $row['tahun'] . "',";
                    }
                ?>],
                datasets: [{
                    label: 'Pengunjung',
                    data: [<?php
                        $dataPoints = $conn->query("SELECT tahun, SUM(pengunjung) AS total FROM tourism_data GROUP BY tahun");
                        while ($row = $dataPoints->fetch_assoc()) {
                            echo $row['total'] . ",";
                        }
                    ?>],
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: false
                }]
            }
        });
    </script>
</body>
</html>
