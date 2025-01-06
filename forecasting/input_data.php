<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debugging: Cetak semua data POST yang diterima
    // echo "<h2>Debug Information</h2>";
    // echo "<h3>Raw POST Data:</h3>";
    // echo "<pre>";
    // print_r($_POST);
    // echo "</pre>";

    // Validasi data
    $requiredFields = ['data_name', 'jumlah_tahun', 'jumlah_seasonal_index', 'seasonal_index', 'data_pengunjung'];
    $missingFields = [];

    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || (is_array($_POST[$field]) && empty($_POST[$field]))) {
            $missingFields[] = $field;
        }
    }

    if (!isset($_POST['seasonal_index'])) {
        // echo "<h3 style='color:red'>Error: Seasonal index tidak terkirim melalui POST.</h3>";
        // echo "<h4>Debug POST:</h4>";
        // echo "<pre>";
        // print_r($_POST);
        // echo "</pre>";
        exit;
    }
    
    if (empty($missingFields)) {
        try {
            // Sanitasi input
            $dataName = $conn->real_escape_string($_POST['data_name']);
            $jumlahTahun = intval($_POST['jumlah_tahun']);
            $jumlahSI = intval($_POST['jumlah_seasonal_index']);
            $seasonalIndex = $_POST['seasonal_index'];
            $dataPengunjung = $_POST['data_pengunjung'];

            // Debugging: Validasi input
            // echo "<h3>Validated Inputs:</h3>";
            // echo "Data Name: $dataName<br>";
            // echo "Jumlah Tahun: $jumlahTahun<br>";
            // echo "Jumlah Seasonal Index: $jumlahSI<br>";

            $conn->begin_transaction();

            foreach ($dataPengunjung as $tahun => $bulanData) {
                foreach ($bulanData as $bulanIndex => $pengunjung) {
                    $siIndex = $bulanIndex % $jumlahSI; // Rotasi seasonal index
                    $si = $conn->real_escape_string($seasonalIndex[$siIndex]);
                    $pengunjung = $conn->real_escape_string($pengunjung);

                    // Simpan data ke tabel (tanpa kolom bulan)
                    $sql = "INSERT INTO tourism_data (nama_data, tahun, tempat, pengunjung) 
                            VALUES ('$dataName', $tahun, '$si', $pengunjung)";
                    
                    if (!$conn->query($sql)) {
                        throw new Exception("Insert failed: " . $conn->error);
                    }
                }
            }

            $conn->commit();
            echo "<script>
            alert('Data berhasil disimpan ke database.');
        </script>";

        } catch (Exception $e) {
            $conn->rollback();
            echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
        }
    } else {
        echo "<h3>Form tidak lengkap. Field yang hilang:</h3>";
        echo "<ul>";
        foreach ($missingFields as $field) {
            echo "<li>$field</li>";
        }
        echo "</ul>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Input Forecasting</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #f8f9fa;
            margin-top: 50px; /* Memberikan jarak antara navbar dan form */
        }
        
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1, h2, h3 {
            color: #343a40;
        }
        button {
            margin-top: 10px;
        }
    </style>
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

    <!-- Form Input -->
    <div class="container">
        <div class="form-container">
            <h1 class="text-center">Form Input Data Forecasting</h1>

            <!-- Step 1: Masukkan Nama Data -->
            <form id="step1">
                <div class="mb-3">
                    <label for="data_name" class="form-label">Nama Data:</label>
                    <input type="text" id="data_name" name="data_name" class="form-control" required>
                </div>
                <button type="button" class="btn btn-primary w-100" onclick="goToStep(2)">Lanjut</button>
            </form>

            <!-- Step 2: Masukkan Jumlah Tahun dan Seasonal Index -->
            <form id="step2" style="display: none;">
                <div class="mb-3">
                    <label for="jumlah_tahun" class="form-label">Jumlah Tahun Data:</label>
                    <input type="number" id="jumlah_tahun" name="jumlah_tahun" class="form-control" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="jumlah_seasonal_index" class="form-label">Jumlah Seasonal Index:</label>
                    <input type="number" id="jumlah_seasonal_index" name="jumlah_seasonal_index" class="form-control" min="1" required>
                </div>
                <button type="button" class="btn btn-primary w-100" onclick="generateSeasonalIndexForm()">Lanjut</button>
            </form>

            <!-- Step 3: Masukkan Seasonal Index -->
            <form id="step3" style="display: none;">
                <div id="seasonal_index_container"></div>
                <button type="button" class="btn btn-primary w-100" onclick="generateDataForm()">Lanjut</button>
            </form>

            <!-- Step 4: Masukkan Data Pengunjung -->
            <form id="step4" action="" method="post" style="display: none;">
                <input type="hidden" name="data_name" id="hidden_data_name">
                <input type="hidden" name="jumlah_tahun" id="hidden_jumlah_tahun">
                <input type="hidden" name="jumlah_seasonal_index" id="hidden_jumlah_seasonal_index">
                <div id="data_pengunjung_container"></div>
                <button type="submit" class="btn btn-success w-100">Submit</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function goToStep(step) {
            document.getElementById("step1").style.display = step === 2 ? "none" : "block";
            document.getElementById("step2").style.display = step === 2 ? "block" : "none";
        }

        function generateSeasonalIndexForm() {
            const jumlahSI = document.getElementById("jumlah_seasonal_index").value;
            const container = document.getElementById("seasonal_index_container");
            container.innerHTML = `<h2>Masukkan Seasonal Index (${jumlahSI} per tahun)</h2>`;

            for (let i = 1; i <= jumlahSI; i++) {
                container.innerHTML += ` 
                    <div class="mb-3">
                        <label for="seasonal_index_${i}" class="form-label">Seasonal Index ${i}:</label>
                        <input type="text" name="seasonal_index[]" id="seasonal_index_${i}" class="form-control" step="0.01" required>
                    </div>
                `;
            }

            document.getElementById("step2").style.display = "none";
            document.getElementById("step3").style.display = "block";
        }

        function generateDataForm() {
            const jumlahTahun = document.getElementById("jumlah_tahun").value;
            const jumlahSI = document.getElementById("jumlah_seasonal_index").value;

            const seasonalIndexInputs = document.getElementsByName("seasonal_index[]");
            const seasonalIndex = Array.from(seasonalIndexInputs).map(input => parseFloat(input.value));

            if (seasonalIndex.length === parseInt(jumlahSI) && seasonalIndex.every(val => !isNaN(val))) {
                const container = document.getElementById("data_pengunjung_container");
                container.innerHTML = `<h2>Masukkan Data Pengunjung</h2>`;

                for (let i = 1; i <= jumlahTahun; i++) {
                    container.innerHTML += `<h3>Tahun ${i}</h3>`;
                    for (let j = 0; j < jumlahSI; j++) {
                        container.innerHTML += `
                            <div class="mb-3">
                                <label for="data_pengunjung_${i}_${j}" class="form-label">Periode ${j + 1}:</label>
                                <input type="number" id="data_pengunjung_${i}_${j}" name="data_pengunjung[${i}][${j}]" class="form-control" required>
                            </div>
                        `;
                    }
                }

                // Masukkan seasonal_index[] ke form step4
                const step4Form = document.getElementById("step4");
                seasonalIndex.forEach((val, index) => {
                    const hiddenInput = document.createElement("input");
                    hiddenInput.type = "hidden";
                    hiddenInput.name = "seasonal_index[]";
                    hiddenInput.value = val;
                    step4Form.appendChild(hiddenInput);
                });

                // Hidden fields
                document.getElementById("hidden_data_name").value = document.getElementById("data_name").value;
                document.getElementById("hidden_jumlah_tahun").value = jumlahTahun;
                document.getElementById("hidden_jumlah_seasonal_index").value = jumlahSI;

                document.getElementById("step3").style.display = "none";
                document.getElementById("step4").style.display = "block";
            } else {
                alert("Pastikan semua seasonal index diisi dengan angka.");
            }
        }
    </script>
</body>
</html>
