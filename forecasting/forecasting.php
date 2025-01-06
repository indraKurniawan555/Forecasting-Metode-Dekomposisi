<?php
// Function to smooth data using moving average
function smoothData($data, $windowSize = 3) {
    // Validasi input
    if (empty($data) || $windowSize <= 0) {
        return $data;
    }

    // Pastikan ukuran jendela ganjil untuk perataan simetris
    if ($windowSize % 2 == 0) {
        $windowSize++;
    }

    $smoothedData = [];
    $halfWindow = floor($windowSize / 2);

    // Haluskan data
    for ($i = 0; $i < count($data); $i++) {
        // Kumpulkan data dalam jendela
        $window = [];
        $windowIndices = [];
        
        // Hitung indeks awal dan akhir untuk jendela
        $startIndex = max(0, $i - $halfWindow);
        $endIndex = min(count($data) - 1, $i + $halfWindow);
        
        for ($j = $startIndex; $j <= $endIndex; $j++) {
            $window[] = $data[$j];
            $windowIndices[] = $j;
        }

        // Hitung rata-rata bergerak berbobot
        $weights = [];
        $totalWeight = 0;
        for ($k = 0; $k < count($window); $k++) {
            // Berikan bobot lebih pada titik yang lebih dekat ke pusat
            $weight = $windowSize - abs($windowIndices[$k] - $i);
            $weights[] = $weight;
            $totalWeight += $weight;
        }

        // Hitung rata-rata berbobot
        $smoothedValue = 0;
        for ($k = 0; $k < count($window); $k++) {
            $smoothedValue += $window[$k] * ($weights[$k] / $totalWeight);
        }

        $smoothedData[] = $smoothedValue;
    }

    return $smoothedData;
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filename = $file['tmp_name'];

    if (is_uploaded_file($filename)) {
        $fileHandle = fopen($filename, 'r');
        $header = fgetcsv($fileHandle, 0, ';');
        $data = [];

        while (($row = fgetcsv($fileHandle, 0, ';')) !== false) {
            $rowClean = array_map(function ($value) {
                return trim(preg_replace('/[^0-9.]/', '', $value));
            }, $row);

            $data[] = array_combine($header, $rowClean);
        }
        fclose($fileHandle);

        include 'includes/db.php';
        foreach ($data as $row) {
            $nama_data = $conn->real_escape_string($row['nama_data']);
            $tahun = $conn->real_escape_string($row['tahun']);
            $tempat = $conn->real_escape_string($row['tempat']);
            $pengunjung = $conn->real_escape_string(str_replace([' ', ','], '', $row['pengunjung']));

            if (!$conn->query("INSERT INTO tourism_data (nama_data, tahun, tempat, pengunjung) VALUES ('$nama_data', '$tahun', '$tempat', '$pengunjung')")) {
                echo "Error: " . $conn->error . "<br>";
            }
        }

        echo "Data berhasil diunggah dan diproses.";
    } else {
        echo "File tidak valid.";
    }
}

include 'includes/db.php';

// Filter berdasarkan nama_data
$namaDataFilter = isset($_POST['nama_data_filter']) ? $conn->real_escape_string($_POST['nama_data_filter']) : '';

$data = [];
$bulan = ['Desember', 'November', 'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei', 'April', 'Maret', 'Februari', 'Januari'];

// Deteksi apakah data yang diminta berisi bulan atau angka
$bulanDalamDB = [];
$angkaDalamDB = [];
$resultBulan = $conn->query("SELECT DISTINCT tempat FROM tourism_data WHERE nama_data = '$namaDataFilter'");
while ($row = $resultBulan->fetch_assoc()) {
    // Cek apakah tempat adalah bulan
    if (in_array($row['tempat'], $bulan)) {
        $bulanDalamDB[] = $row['tempat'];
    } else {
        // Jika tidak, anggap tempat tersebut sebagai angka
        if (is_numeric($row['tempat'])) {
            $angkaDalamDB[] = (float) $row['tempat'];
        }
    }
}

// Replace the existing ordering logic with this:
$orderClause = '';
if (!empty($bulanDalamDB)) {
    usort($bulanDalamDB, function ($a, $b) use ($bulan) {
        return array_search($a, $bulan) - array_search($b, $bulan);
    });
    $orderClause = "FIELD(tempat, '" . implode("','", $bulanDalamDB) . "')";
} elseif (!empty($angkaDalamDB)) {
    // Sort numbers from largest to smallest
    $orderClause = "CAST(tempat AS UNSIGNED) DESC";
} else {
    $orderClause = "tempat DESC";  // Default for non-numeric, non-month places
}

// Ambil data dengan urutan yang sesuai
$result = $conn->query("SELECT * FROM tourism_data 
                        WHERE nama_data = '$namaDataFilter' 
                        ORDER BY tahun DESC, $orderClause");

if ($result->num_rows > 0) {
    echo "<h3>Data berhasil diambil: {$result->num_rows} baris</h3>";
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'tahun' => $row['tahun'],
            'tempat' => $row['tempat'],
            'pengunjung' => floatval(str_replace(',', '.', $row['pengunjung'])),
        ];
    }
} else {
    echo "<h3>Tidak ada data yang ditemukan untuk nama_data: $namaDataFilter.</h3>";
    exit();
}

// Add a check for data type (smoothed or original)
$useSmoothedData = isset($_POST['data_type']) && $_POST['data_type'] == 'smoothed';

// Extract numerical data for smoothing or use original data
$numericalData = array_column($data, 'pengunjung');
        
// Smooth the data if selected
if ($useSmoothedData) {
    $smoothedNumericalData = smoothData($numericalData);

    // Replace original data with smoothed data
    foreach ($data as $index => &$row) {
        $row['pengunjung'] = $smoothedNumericalData[$index] ?? $row['pengunjung'];
    }
}

// Modify the forecasting calculations to be more robust
$pengunjung = array_column($data, 'pengunjung');


define("DEFAULT_K", 4);
        // $k = DEFAULT_K; // Inisialisasi awal
    $k=4;
        // Perbarui $k hanya jika ada input dari form
        if (isset($_POST['k']) && is_numeric($_POST['k'])) {
            $k = (int) $_POST['k'];
            if ($k < 1) {
                $k = 1; // Pastikan $k minimal 1
            }
        }
// $k = 4;
// $k2 = $k+1;

if (!$k || $k <= 1) {
    echo "<h3>Nilai k harus diisi dan lebih besar dari 0.</h3>";
    exit();
}

$pengunjung = array_column($data, 'pengunjung');
$MA = [];
for ($i = $k; $i < count($pengunjung); $i++) {
    $sum = 0;
    for ($j = 0; $j < $k; $j++) {
        $sum += $pengunjung[$i - $j];
    }
    $MA[$i - $k] = $sum / $k;
}


$CMA = [];
for ($i = 0; $i < count($MA) - 1; $i++) {
    $CMA[$i] = ($MA[$i] + $MA[$i + 1]) / 2;
}

if (count($CMA) == 0) {
    echo "Tidak ada nilai CMA yang valid.<br>";
    exit();
}

$n = count($pengunjung);
$sumX = $sumY = $sumXY = $sumX2 = 0;

for ($i = $n - 1; $i >= 0; $i--) {
    $x = $i;
    $y = $data[$n - 1 - $i]['pengunjung'];
    $sumX += $x;
    $sumY += $y;
    $sumXY += $x * $y;
    $sumX2 += $x * $x;
}

$denominator = ($n * $sumX2) - ($sumX * $sumX);
if ($denominator == 0) {
    echo "Perhitungan trend gagal karena denominator nol.<br>";
    exit();
}

$a = ($sumY * $sumX2 - $sumX * $sumXY) / $denominator;
$b = ($n * $sumXY - $sumX * $sumY) / $denominator;

$CMAT = [];
for ($i = $n; $i >= 0; $i--) {
    $x = $i;
    $CMAT[$n - 1 - $i] = $a + $b * $x;
}

$CF = [];
for ($i = 0; $i < count($CMA); $i++) {
    $CF[$i] = $CMAT[$i] != 0 ? $CMA[$i] / $CMAT[$i] : 0;
}

$SI = [];
$daftartempat =[];
// And update the summary view query similarly:
$hasilsi = $conn->query("SELECT * 
FROM tourism_summary_view
WHERE nama_data = '$namaDataFilter' 
ORDER BY 
    -- Mengurutkan bulan dari Desember ke Januari
    FIELD(tempat, 'Desember', 'November', 'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei', 'April', 'Maret', 'Februari', 'Januari') ASC,
    
    -- Sort numbers from largest to smallest
    CASE 
        WHEN tempat NOT IN ('Desember', 'November', 'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei', 'April', 'Maret', 'Februari', 'Januari') 
        THEN CAST(tempat AS UNSIGNED)
        ELSE 0
    END DESC;
");

if ($hasilsi->num_rows > 0) {
    while ($row = $hasilsi->fetch_assoc()) {
        $SI[] = [
            'hasil' => $row['hasil'],
        ];
        $daftarTempat[] = $row['tempat'];
    }
} else {
    echo "<h3>View tourism_summary_view tidak memiliki data.</h3>";
    exit();
}

if (count($SI) > 0) {
    $totalData = count($data);
    $repeatedSI = [];
    for ($i = 0; $i < $totalData; $i++) {
        $repeatedSI[$i] = $SI[$i % count($SI)];
    }
} else {
    echo "<h3>View tourism_summary_view tidak memiliki data.</h3>";
    exit();
}

// echo "<pre>";
// print_r($SI);             // Data hasil
// print_r($daftarTempat);   // Data tempat
// echo "</pre>";


$FT = [];
for ($i = 0; $i < count($CMA); $i++) {
    $FT[$i] = $CMAT[$i] + $CF[$i] + $repeatedSI[$i]['hasil'] + 1;
}

$MAPE = [];
for ($i = 0; $i < count($CMA); $i++) {
    if ($pengunjung[$i] != 0) { // Hindari pembagian dengan nol
        $MAPE[$i] = abs(($pengunjung[$i] - $FT[$i]) / $pengunjung[$i]);
        
    } else {
        $MAPE[$i] = 0; // Atur nilai MAPE ke 0 jika pengunjung adalah 0
    }
}


// Hitung MAPE secara keseluruhan
$totalMAPE = 0;
$validMAPECount = 0;

foreach ($MAPE as $index => $value) {
    if ($value > 0) { // Hanya hitung MAPE yang valid
        $totalMAPE += $value;
        $validMAPECount++;
        // echo "MAPE[$index]: " . number_format($value * 100, 2) . "%<br>";
    } else {
        echo "MAPE[$index]: Tidak valid (0)<br>";
    }
}

if ($validMAPECount > 0) {
    $overallMAPE = ($totalMAPE / $validMAPECount) * 100; // Dalam persentase
    // echo "<br>MAPE secara keseluruhan: " . number_format($overallMAPE, 2) . "%<br>";
} else {
    echo "<br>Tidak ada nilai MAPE yang valid untuk dihitung.<br>";
}

$mse = 0;
$rmse = 0;
$mape = 0;
$errorCount = 0;


$mse = 0;
$sumSquaredError = 0; // Variabel untuk menyimpan jumlah error^2

// echo "<table border='1'>";
// echo "<tr>
//         <th>No</th>
//         <th>Pengunjung</th>
//         <th>Prediksi (FT)</th>
//         <th>Error</th>
//         <th>Error^2</th>
//       </tr>";

for ($i = 0; $i < count($CMA); $i++) {
    $error = $pengunjung[$i] - $FT[$i];
    $errorSquared = pow($error, 2); // Hitung error^2
    $sumSquaredError += $errorSquared; // Tambahkan error^2 ke dalam sumSquaredError
    
    // Tampilkan data dalam tabel
    // echo "<tr>
    //         <td>" . ($i + 1) . "</td>
    //         <td>" . $pengunjung[$i] . "</td>
    //         <td>" . $FT[$i] . "</td>
    //         <td>" . $error . "</td>
    //         <td>" . $errorSquared . "</td>
    //       </tr>";
}

// echo "</table>";

$mse = $sumSquaredError / count($CMA); // Rata-rata error kuadrat
$rmse = sqrt($mse); // Akar kuadrat dari MSE

// Tampilkan hasil
// echo "Sum of Squared Errors (SSE): " . $sumSquaredError . "<br>";
// echo "MSE: " . $mse . "<br>";
// echo "RMSE: " . $rmse . "<br>";


// // Output hasil
// echo "<h3>Nilai MSE: " . number_format($mse, 4) . "</h3>";
// echo "<h3>Nilai RMSE: " . number_format($rmse, 4) . "</h3>";

// Forecast beberapa periode ke depan berdasarkan nilai \$k
$forecasts = [];
$forecastValues = [];

for ($i = 0; $i < $k; $i++) {
    $nextPeriodIndex = $n + $i; // Indeks waktu untuk periode ke depan

    // Hitung Next Moving Average (MA)
    $nextMA = 0;
    if ($k <= $n + $i) {
        $sumNextMA = 0;
        for ($j = 0; $j < $k; $j++) {
            $sumNextMA += $pengunjung[$j]; // Gunakan data secara berurutan mulai dari index 0
        }
        $nextMA = $sumNextMA / $k;
    }

    // Hitung CMA untuk periode ke depan
    $nextCMA = 0;
    if (count($MA) > 0) {
        $nextCMA = ($MA[0] + $nextMA) / 2;
    }

    // Hitung CMAT untuk periode ke depan
    $nextCMAT = $a + $b * $nextPeriodIndex;

    // Hitung CF untuk periode ke depan
    $nextCF = $nextCMAT != 0 ? $nextCMA / $nextCMAT : 0;

    // Ambil SI untuk periode ke depan
    $nextSI = 0;
    if (count($SI) > 0) {
        $indexSI = count($SI) - 1 - $i; // Ambil dari indeks terbesar ke terkecil
        if ($indexSI >= 0) {
            $nextSI = $SI[$indexSI]['hasil'];
        }
    }

    // Hitung forecast untuk periode ke depan
    $nextFT = $nextCMAT + $nextCF + $nextSI + 1;
    $forecastValues[] = $nextFT;

    // Simpan hasil forecast untuk periode ini
    $forecasts[] = [
        'periode' => $nextPeriodIndex + 1,
        'MA' => $nextMA,
        'CMA' => $nextCMA,
        'CMAT' => $nextCMAT,
        'CF' => $nextCF,
        'SI' => $nextSI,
        'FT' => $nextFT,
    ];
}


// echo "<tr>
//     <td colspan='10'><strong>MSE</strong></td>
//     <td colspan='2'><strong>" . number_format($mse, 2) . "</strong></td>
// </tr>";
// echo "<tr>
//     <td colspan='10'><strong>RMSE</strong></td>
//     <td colspan='2'><strong>" . number_format($rmse, 2) . "</strong></td>
// </tr>";
// echo "<tr>
//     <td colspan='10'><strong>MAPE</strong></td>
//     <td colspan='2'><strong>" . number_format($mape, 2) . "%</strong></td>
// </tr>";

// echo "</table>";
// echo "<h3>Nilai k yang digunakan: $k</h3>";
// echo "<h3>Data Perhitungan Forecasting dan Trend Linear</h3>";
// echo "<table border='1'>
//     <tr>
//         <th>Tahun</th>
//         <th>Tempat</th>
//         <th>Pengunjung</th>
//         <th>MA</th>
//         <th>CMA</th>
//         <th>x</th>
//         <th>x^2</th>
//         <th>xy</th>
//         <th>CMAT</th>
//         <th>CF</th>
//         <th>SI</th>
//         <th>FT</th>
//         <th>MAPE</th>
//     </tr>";

// for ($i = 0; $i < count($data); $i++) {
//     $x = $n - $i - 1;
//     $xSquared = $x * $x;
//     $xy = $x * ($data[$i]['pengunjung'] ?? 0);
//     $cmaValue = $CMA[$i] ?? '-';
//     $cmatValue = $CMAT[$i] ?? '-';
//     $cfValue = $CF[$i] ?? '-';
//     $siValue = $repeatedSI[$i]['hasil'] ?? '-';

//     echo "<tr>
//         <td>{$data[$i]['tahun']}</td>
//         <td>{$data[$i]['tempat']}</td>
//         <td>{$data[$i]['pengunjung']}</td>
//         <td>" . ($MA[$i] ?? '-') . "</td>
//         <td>$cmaValue</td>
//         <td>$x</td>
//         <td>$xSquared</td>
//         <td>$xy</td>
//         <td>$cmatValue</td>
//         <td>$cfValue</td>
//         <td>$siValue</td>
//         <td>" . ($FT[$i] ?? '-') . "</td>
//         <td>" . ($MAPE[$i] ?? '-') . "%</td>
//     </tr>";
// }


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forecasting Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="style.css" rel="stylesheet">
</head>
</head>
<body>
<header>
        <div class="logo">
        <img src="iconforecast.png " width="50ox" alt="Logo Forecast" />
            <span>Forecasting</span>
        </div>
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="input_data.php">Input Data</a></li>
                <li><a href="forecasting.php">Forecasting</a></li>
                <li><a href="edit_data.php">Edit Data <i class="fas fa-caret-down"></i></a>
                    
                </li>
                <li><a href="#">Tentang <i class="fas fa-caret-down"></i></a></li>
            </ul>
        </nav>
        <div class="login-button">
            <a href="#">Login</a>
        </div>
    </header>
<div class="container my-5">
    <h1 class="text-center">Forecasting Dashboard</h1>

    <form method="post" enctype="multipart/form-data" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label for="nama_data_filter" class="form-label">Pilih Nama Data:</label>
                    <select name="nama_data_filter" id="nama_data_filter" class="form-select">
                        <?php
                        $result = $conn->query("SELECT DISTINCT nama_data FROM tourism_data");
                        while ($row = $result->fetch_assoc()) {
                            $selected = isset($_POST['nama_data_filter']) && $_POST['nama_data_filter'] == $row['nama_data'] ? 'selected' : '';
                            echo "<option value='{$row['nama_data']}' $selected>{$row['nama_data']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="k" class="form-label">Masukkan Nilai k:</label>
                    <input type="number" name="k" id="k" class="form-control" required min="1" value="<?php echo isset($_POST['k']) ? $_POST['k'] : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="data_type" class="form-label">Tipe Data:</label>
                    <select name="data_type" id="data_type" class="form-select">
                        <option value="original" <?php echo (!isset($_POST['data_type']) || $_POST['data_type'] == 'original') ? 'selected' : ''; ?>>Data Asli</option>
                        <option value="smoothed" <?php echo (isset($_POST['data_type']) && $_POST['data_type'] == 'smoothed') ? 'selected' : ''; ?>>Data Halus</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

    <!-- Grafik Data Pengunjung dan Forecast -->
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title">Grafik Data Pengunjung dan Forecast</h3>
            <canvas id="forecastChart"></canvas>
        </div>
    </div>

    <!-- Grafik MAPE -->
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title">Grafik MAPE</h3>
            <canvas id="mapeChart"></canvas>
        </div>
    </div>

    <!-- Grafik Seasonal Index -->
<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">Grafik Seasonal Index (SI)</h3>
        <canvas id="siChart"></canvas>
    </div>
</div>

<!-- Grafik Error -->
<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">Grafik Error</h3>
        <canvas id="errorChart"></canvas>
    </div>
</div>

<!-- Grafik CMAT -->
<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">Grafik Centered Moving Average Trend (CMAT)</h3>
        <canvas id="cmatChart"></canvas>
    </div>
</div>

<div class="card shadow-sm">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">Hasil Perhitungan</h3>
            
            
            <div class="alert alert-success text-center" role="alert">
                <h4 class="alert-heading">Nilai MSE</h4>
                <p class="lead"><?php echo number_format($mse, 4); ?></p>
            </div>

            
            <div class="alert alert-warning text-center" role="alert">
                <h4 class="alert-heading">Nilai RMSE</h4>
                <p class="lead"><?php echo number_format($rmse, 4); ?></p>
            </div>


            <div class="alert alert-primary text-center" role="alert">
                <h4 class="alert-heading">Nilai MAPE</h4>
                <p class="lead"><?php echo number_format($overallMAPE, 2); ?></p>
            </div>

        </div>
    </div>
    <!-- Tabel Detail Perhitungan -->
    <button class="btn btn-secondary mb-3" id="toggleDetails">Lihat Detail Perhitungan</button>
    <div id="details" style="display: none;">
            <h3>Data Perhitungan Forecasting dan Trend Linear</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tahun</th>
                        <th>Tempat</th>
                        <th>Pengunjung</th>
                        <th>MA</th>
                        <th>CMA</th>
                        <th>x</th>
                        <th>x^2</th>
                        <th>xy</th>
                        <th>CMAT</th>
                        <th>CF</th>
                        <th>SI</th>
                        <th>FT</th>
                        <th>Error</th>
                        <th>MAPE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Place these lines immediately after the opening PHP tag
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

                    for ($i = count($data) - 1; $i >= 0; $i--) {
                        $x = $n - $i - 1;
                        $xSquared = $x * $x;
                        $xy = $x * ($data[$i]['pengunjung'] ?? 0);
                        $cmaValue = $CMA[$i] ?? '-';
                        $cmatValue = $CMAT[$i] ?? '-';
                        $cfValue = $CF[$i] ?? '-';
                        $siValue = $repeatedSI[$i]['hasil'] ?? '-';
                        $error = isset($FT[$i]) ? ($data[$i]['pengunjung'] - $FT[$i]) : '-';

                        echo "<tr>
                            <td>{$data[$i]['tahun']}</td>
                            <td>{$data[$i]['tempat']}</td>
                            <td>{$data[$i]['pengunjung']}</td>
                            <td>" . ($MA[$i] ?? '-') . "</td>
                            <td>$cmaValue</td>
                            <td>$x</td>
                            <td>$xSquared</td>
                            <td>$xy</td>
                            <td>$cmatValue</td>
                            <td>$cfValue</td>
                            <td>$siValue</td>
                            <td>" . ($FT[$i] ?? '-') . "</td>
                            <td>$error</td>
                            <td>" . ($MAPE[$i] ?? '-') . "%</td>
                        </tr>";
                    }
                    
                    ?>
                </tbody>
            </table>
            <div class="container mt-5">
    
</div>
        </div>
    </div>
</div>

<script>
const mapeData = <?php echo json_encode($MAPE); ?>;

// Data untuk Grafik Forecast
const forecastLabels = <?php echo json_encode(array_reverse(array_column($data, 'tahun'))); ?>;
const pengunjungData = <?php echo json_encode(array_reverse(array_column($data, 'pengunjung'))); ?>;

// Remove the initial null values from forecastData
const forecastData = <?php echo json_encode(array_reverse($FT)); ?>;

// Data tambahan untuk forecast baru menggunakan $forecastValues
const additionalForecast = [...<?php echo json_encode($forecastValues); ?>]; // Data tambahan untuk forecast baru

// Menghitung panjang dari additionalForecast dan menambah 1
const k = additionalForecast.length + 1; // Panjang data additionalForecast + 1

// Mengosongkan data awal pada forecastData sesuai rumus k
const emptyForecastData = Array(k).fill(null); // Mengosongkan k data pertama
console.log(emptyForecastData);

// Menggabungkan emptyForecastData dengan forecastData
const updatedForecastData = [...emptyForecastData, ...forecastData];
console.log(updatedForecastData);

// Geser additionalForecast ke kanan dengan menambahkan null di awal
const shiftedAdditionalForecast = Array(updatedForecastData.length).fill(null).concat(additionalForecast);

// Gabungkan updatedForecastData terlebih dahulu, lalu shiftedAdditionalForecast
const combinedForecastData = [...updatedForecastData, ...shiftedAdditionalForecast];

// Menambahkan tahun untuk forecast masa depan ke dalam forecastLabels
const futureYears = 1; // Menambahkan 5 tahun masa depan, sesuaikan sesuai kebutuhan
const lastYear = parseInt(forecastLabels[forecastLabels.length - 1]); // Ambil tahun terakhir yang ada
const futureLabels = Array.from({ length: futureYears }, (_, index) => (lastYear + 1).toString());

// Gabungkan tahun lama dan tahun masa depan
const extendedForecastLabels = [...forecastLabels, ...futureLabels];

// Pastikan combinedForecastData diperpanjang dengan nilai-nilai forecast masa depan
const extendedCombinedForecastData = [...combinedForecastData, ...Array(futureYears).fill(null)];

// Debug: Log nilai-nilai untuk memeriksa apakah array sudah terisi dengan benar
console.log('Extended Forecast Labels:', extendedForecastLabels);
console.log('Extended Combined Forecast Data:', extendedCombinedForecastData);

// Membuat grafik dengan Chart.js
const ctxForecast = document.getElementById('forecastChart').getContext('2d');
const forecastChart = new Chart(ctxForecast, {
    type: 'line',
    data: {
        labels: extendedForecastLabels,
        datasets: [
            {
                label: 'Data Pengunjung',
                data: pengunjungData,
                borderColor: 'blue',
                backgroundColor: 'rgba(0, 0, 255, 0.1)',
                fill: true
            },
            {
                label: 'Forecast (FT)',
                data: updatedForecastData,
                borderColor: 'green',
                backgroundColor: 'rgba(0, 255, 0, 0.1)',
                fill: true
            },
            {
                label: 'Forecast (Additional)',
                data: shiftedAdditionalForecast,
                borderColor: 'red',
                borderDash: [5, 5], // Gaya garis putus-putus untuk membedakan
                backgroundColor: 'rgba(255, 0, 0, 0.1)',
                fill: false
            },
            {
                label: 'Combined Forecast',
                data: extendedCombinedForecastData,
                borderColor: 'purple',
                backgroundColor: 'rgba(128, 0, 128, 0.1)',
                fill: false,
                borderWidth: 2,  // Agar garis penghubung lebih jelas
                tension: 0.1     // Membuat garis lebih halus
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Tahun'
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Pengunjung'
                }
            }
        }
    }
});

// Membalik urutan errorData
const reversedMAPE = mapeData.reverse();

// Debug untuk memastikan hasil
console.log('Reversed MAPE:', reversedMAPE);
console.log('MAPE Data:', mapeData)

const MAPEupdate = [...emptyForecastData,...mapeData];
console.log('MAPEupdate:' ,MAPEupdate);

// Data untuk Grafik MAPE
const ctxMape = document.getElementById('mapeChart').getContext('2d');
const mapeChart = new Chart(ctxMape, {
    type: 'bar',
    data: {
        labels: forecastLabels,
        datasets: [
            {
                label: 'MAPE',
                data: MAPEupdate,
                backgroundColor: 'orange'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
        },
    }
});

const forecastLabels2 = <?php 
    // Ambil kolom 'tempat' dari $data dan hapus duplikat
    $tempatUnik = array_unique(array_column($data, 'tempat'));

    // Cek apakah data berupa angka atau bulan
    $isNumeric = ctype_digit(implode('', $tempatUnik));

    if ($isNumeric) {
        // Jika data berupa angka, urutkan dari kecil ke besar
        sort($tempatUnik, SORT_NUMERIC);
    } else {
        // Urutan referensi bulan
        $bulanUrut = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Urutkan berdasarkan referensi bulan
        usort($tempatUnik, function($a, $b) use ($bulanUrut) {
            $posA = array_search($a, $bulanUrut);
            $posB = array_search($b, $bulanUrut);

            // Jika salah satu data tidak ditemukan dalam daftar bulan, letakkan di akhir
            if ($posA === false) return 1;
            if ($posB === false) return -1;

            return $posA - $posB;
        });
    }

    // Konversi ke format JSON
    echo json_encode($tempatUnik);
?>;

console.log(forecastLabels2);

// Data untuk Grafik SI (Seasonal Index)
const siData = <?php echo json_encode(array_column($SI, 'hasil')); ?>;
const ctxSI = document.getElementById('siChart').getContext('2d');
const siChart = new Chart(ctxSI, {
    type: 'bar',
    data: {
        labels: forecastLabels2,
        datasets: [
            {
                label: 'Seasonal Index',
                data: siData,
                backgroundColor: 'lightgreen'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
        },
        scales: {
            y: {
                title: {
                    display: true,
                    text: 'Seasonal Index Value'
                }
            }
        }
    }
});

// Data untuk Grafik Error
const errorData = <?php echo json_encode(array_map(function($pengunjung, $ft) { 
    return $pengunjung - $ft; 
}, array_column($data, 'pengunjung'), $FT)); ?>;
// Membalik urutan errorData
const reversedErrorData = errorData.reverse();

// Debug untuk memastikan hasil
console.log(reversedErrorData);


const errorDataupdate = [...emptyForecastData, ...reversedErrorData.slice(k)];
const ctxError = document.getElementById('errorChart').getContext('2d');
const errorChart = new Chart(ctxError, {
    type: 'bar',
    data: {
        labels: forecastLabels,
        datasets: [
            {
                label: 'Forecasting Error',
                data: errorDataupdate,
                backgroundColor: 'salmon',
                borderColor: 'red'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
        },
        scales: {
            y: {
                title: {
                    display: true,
                    text: 'Error Value'
                }
            }
        }
    }
});

// Data untuk Grafik CMAT
const cmatDataRaw = <?php echo json_encode($CMAT); ?>;
console.log('Raw CMAT Data:', cmatDataRaw);

// Ambil hanya nilai (value) dari data CMAT
const cmatData = Object.values(cmatDataRaw);
console.log('Processed CMAT Data:', cmatData);

const revetsedCMATData = cmatData.reverse();
const CMATDataupdate = [...revetsedCMATData.slice(1)];
const ctxCMAT = document.getElementById('cmatChart').getContext('2d');
const cmatChart = new Chart(ctxCMAT, {
    type: 'line',
    data: {
        labels: forecastLabels,
        datasets: [
            {
                label: 'Centered Moving Average Trend (CMAT)',
                data: CMATDataupdate,
                borderColor: 'purple',
                backgroundColor: 'rgba(128, 0, 128, 0.1)',
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
        },
        scales: {
            y: {
                title: {
                    display: true,
                    text: 'CMAT Value'
                }
            }
        }
    }
});

// Toggle Tabel Detail Perhitungan
const toggleDetails = document.getElementById('toggleDetails');
const details = document.getElementById('details');
toggleDetails.addEventListener('click', () => {
    if (details.style.display === 'none') {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>