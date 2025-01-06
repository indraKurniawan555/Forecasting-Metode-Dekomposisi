<?php
include 'includes/db.php';

// Handle form submission for editing data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_data'])) {
    $nama_data = $conn->real_escape_string($_POST['nama_data']);
    $tahun_old = $conn->real_escape_string($_POST['tahun_old']);
    $tahun_new = $conn->real_escape_string($_POST['tahun_new']);
    $tempat_old = $conn->real_escape_string($_POST['tempat_old']);
    $tempat_new = $conn->real_escape_string($_POST['tempat_new']);
    $pengunjung = $conn->real_escape_string($_POST['pengunjung']);

    // Update query for the selected nama_data, tahun, and tempat
    $updateQuery = "UPDATE tourism_data 
                    SET tahun = '$tahun_new', tempat = '$tempat_new', pengunjung = '$pengunjung' 
                    WHERE nama_data = '$nama_data' AND tahun = '$tahun_old' AND tempat = '$tempat_old'";
    
    if ($conn->query($updateQuery)) {
        echo "<script>alert('Data berhasil diperbarui.');</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle form submission for adding new data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_data'])) {
    $nama_data_new = $conn->real_escape_string($_POST['nama_data_new']);
    $tahun_new = $conn->real_escape_string($_POST['tahun_new']);
    $tempat_new = $conn->real_escape_string($_POST['tempat_new']);
    $pengunjung = $conn->real_escape_string($_POST['pengunjung']);

    // Insert query for adding new data
    $insertQuery = "INSERT INTO tourism_data (nama_data, tahun, tempat, pengunjung) 
                    VALUES ('$nama_data_new', '$tahun_new', '$tempat_new', '$pengunjung')";
    
    if ($conn->query($insertQuery)) {
        echo "<script>alert('Data berhasil ditambahkan.');</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle form submission for deleting data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_data'])) {
    $nama_data_delete = $conn->real_escape_string($_POST['nama_data_delete']);

    // Delete query for the selected nama_data
    $deleteQuery = "DELETE FROM tourism_data WHERE nama_data = '$nama_data_delete'";
    
    if ($conn->query($deleteQuery)) {
        echo "<script>alert('Data berhasil dihapus.');</script>";
        // Unset the filter to clear the results after deletion
        unset($_POST['nama_data_filter']);
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Fetch data for display
$namaDataFilter = isset($_POST['nama_data_filter']) ? $conn->real_escape_string($_POST['nama_data_filter']) : '';
$query = "SELECT * FROM tourism_data WHERE nama_data = '$namaDataFilter'";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
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
<div class="container mt-5">
    <h3 class="text-center text-primary">Edit Data</h3>

    <!-- Form Filter Nama Data -->
    <form method="post" class="mb-4">
        <div class="form-row">
            <div class="col-md-4">
                <label for="nama_data_filter">Pilih Nama Data:</label>
                <select name="nama_data_filter" id="nama_data_filter" class="form-control" onchange="this.form.submit()">
                    <option value="">Pilih Nama Data</option>
                    <?php
                    $distinctQuery = "SELECT DISTINCT nama_data FROM tourism_data";
                    $distinctResult = $conn->query($distinctQuery);
                    while ($row = $distinctResult->fetch_assoc()) {
                        $selected = ($row['nama_data'] === $namaDataFilter) ? 'selected' : '';
                        echo "<option value='{$row['nama_data']}' $selected>{$row['nama_data']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
    </form>

    <!-- Button to Open the Modal -->
    <button class="btn btn-success mb-4" data-toggle="modal" data-target="#addDataModal">Tambah Data Baru</button>

    <!-- Add New Data Modal -->
    <div class="modal fade" id="addDataModal" tabindex="-1" role="dialog" aria-labelledby="addDataModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDataModalLabel">Tambah Data Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="nama_data_new">Nama Data:</label>
                            <input type="text" name="nama_data_new" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="tahun_new">Tahun:</label>
                            <input type="text" name="tahun_new" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="tempat_new">Tempat:</label>
                            <input type="text" name="tempat_new" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="pengunjung">Pengunjung:</label>
                            <input type="number" name="pengunjung" class="form-control" required>
                        </div>
                        <button type="submit" name="add_data" class="btn btn-primary">Tambah Data</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Display Data Table -->
    <?php if ($result && $result->num_rows > 0): ?>
        <div class="row mb-3">
            <div class="col-md-12">
                <form method="post" id="deleteForm">
                    <input type="hidden" name="nama_data_delete" id="nama_data_delete" value="<?= htmlspecialchars($namaDataFilter) ?>">
                    <?php if (!empty($namaDataFilter)): ?>
                        <button type="submit" name="delete_data" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus semua data untuk <?= htmlspecialchars($namaDataFilter) ?>?')">
                            Hapus Semua Data <?= htmlspecialchars($namaDataFilter) ?>
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Tahun</th>
                    <th>Tempat</th>
                    <th>Pengunjung</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <form method="post">
                            <input type="hidden" name="nama_data" value="<?= htmlspecialchars($row['nama_data']) ?>">
                            <input type="hidden" name="tahun_old" value="<?= htmlspecialchars($row['tahun']) ?>">
                            <input type="hidden" name="tempat_old" value="<?= htmlspecialchars($row['tempat']) ?>">
                            <td>
                                <input type="text" name="tahun_new" value="<?= htmlspecialchars($row['tahun']) ?>" class="form-control" required>
                            </td>
                            <td>
                                <input type="text" name="tempat_new" value="<?= htmlspecialchars($row['tempat']) ?>" class="form-control" required>
                            </td>
                            <td>
                                <input type="number" name="pengunjung" value="<?= htmlspecialchars($row['pengunjung']) ?>" class="form-control" required>
                            </td>
                            <td>
                                <button type="submit" name="edit_data" class="btn btn-success">Simpan</button>
                            </td>
                        </form>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">Tidak ada data untuk nama_data yang dipilih.</div>
    <?php endif; ?>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>