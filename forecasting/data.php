<?php
include 'includes/db.php'; // Koneksi ke database

// Fungsi untuk Menambah Data
if (isset($_POST['submit_data'])) {
    $nama_data = $conn->real_escape_string($_POST['nama_data']);
    $tahun = $conn->real_escape_string($_POST['tahun']);
    $tempat = $conn->real_escape_string($_POST['tempat']);
    $pengunjung = $conn->real_escape_string($_POST['pengunjung']);

    $query = "INSERT INTO tourism_data (nama_data, tahun, tempat, pengunjung) VALUES ('$nama_data', '$tahun', '$tempat', '$pengunjung')";

    if ($conn->query($query)) {
        echo "Data berhasil dimasukkan!";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Menampilkan Data yang Ada
$data = [];
$result = $conn->query("SELECT * FROM tourism_data ORDER BY tahun DESC");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Update Data
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $editResult = $conn->query("SELECT * FROM tourism_data WHERE id = '$edit_id'");
    $editData = $editResult->fetch_assoc();

    if (isset($_POST['update_data'])) {
        $tahun = $conn->real_escape_string($_POST['tahun']);
        $tempat = $conn->real_escape_string($_POST['tempat']);
        $pengunjung = $conn->real_escape_string($_POST['pengunjung']);

        $updateQuery = "UPDATE tourism_data SET tahun = '$tahun', tempat = '$tempat', pengunjung = '$pengunjung' WHERE id = '$edit_id'";

        if ($conn->query($updateQuery)) {
            header("Location: data.php");
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// Delete Data
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $deleteQuery = "DELETE FROM tourism_data WHERE id = '$delete_id'";

    if ($conn->query($deleteQuery)) {
        header("Location: data.php");
    } else {
        echo "Error: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data CRUD</title>
</head>
<body>
    <h2>Data yang Tersedia</h2>
    <table border="1">
        <tr>
            <th>Nama Data</th>
            <th>Tahun</th>
            <th>Tempat</th>
            <th>Pengunjung</th>
            <th>Action</th>
        </tr>
        <?php foreach ($data as $row): ?>
            <tr>
                <td><?php echo $row['nama_data']; ?></td>
                <td><?php echo $row['tahun']; ?></td>
                <td><?php echo $row['tempat']; ?></td>
                <td><?php echo $row['pengunjung']; ?></td>
                <td>
                    <a href="data.php?edit_id=<?php echo $row['id']; ?>">Edit</a> | 
                    <a href="data.php?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php
    if (isset($editData)) {
        // Jika ada edit data
        ?>
        <h2>Edit Data</h2>
        <form action="data.php?edit_id=<?php echo $editData['id']; ?>" method="POST">
            <label for="tahun">Tahun:</label>
            <input type="text" id="tahun" name="tahun" value="<?php echo $editData['tahun']; ?>" required><br>

            <label for="tempat">Tempat:</label>
            <input type="text" id="tempat" name="tempat" value="<?php echo $editData['tempat']; ?>" required><br>

            <label for="pengunjung">Pengunjung:</label>
            <input type="number" id="pengunjung" name="pengunjung" value="<?php echo $editData['pengunjung']; ?>" required><br>

            <button type="submit" name="update_data">Update Data</button>
        </form>
        <?php
    }
    ?>
</body>
</html>
