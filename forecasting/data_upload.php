<?php
include 'includes/db.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Upload Data - Forecasting</title>
</head>
<body>
    <h1>Upload Data</h1>
    <form action="data_upload.php" method="post" enctype="multipart/form-data">
        <input type="file" name="dataset" accept=".csv" required>
        <button type="submit" name="upload">Upload</button>
    </form>

    <?php
    if (isset($_POST['upload'])) {
        $filename = $_FILES['dataset']['tmp_name'];

        if ($_FILES['dataset']['size'] > 0) {
            $file = fopen($filename, "r");

            // Lewati baris header
            fgetcsv($file, 10000, ",");

            $stmt = $conn->prepare("INSERT INTO tes (tahun, tempat, pengunjung) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $tahun, $tempat, $pengunjung);

            while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                $tahun = intval($getData[0]);
                $tempat = $getData[1];
                $pengunjung = intval(str_replace(',', '', $getData[2]));

                $stmt->execute();
            }
            $stmt->close();
            fclose($file);

            echo "<p>Data successfully uploaded.</p>";
        } else {
            echo "<p>No file uploaded or file is empty.</p>";
        }
    }
    ?>
</body>
</html>
