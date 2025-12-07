<?php
// Konfigurasi database
// Sesuaikan dengan konfigurasi database Anda
$host = 'localhost:3307';
$dbname = 'absensi_db';
$username = 'root';
$password = '123';

// Koneksi ke database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Mengatur mode fetch default ke FETCH_ASSOC
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

$success_message = '';
$error_message = '';

// --- Logika Hapus Absensi ---
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'hapus' && isset($_GET['id'])) {
    $id_absensi = $_GET['id'];

    // Validasi ID untuk keamanan
    if (!filter_var($id_absensi, FILTER_VALIDATE_INT)) {
        $error_message = "ID absensi tidak valid!";
    } else {
        $sql_delete = "DELETE FROM absensi WHERE id = :id";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bindParam(':id', $id_absensi, PDO::PARAM_INT);

        if ($stmt_delete->execute()) {
            $success_message = "Data absensi berhasil dihapus!";
            // Redirect untuk menghilangkan parameter GET agar halaman bersih setelah penghapusan
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?status=success_delete");
            exit();
        } else {
            $error_message = "Gagal menghapus data absensi!";
        }
    }
}

// Proses submit absensi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_absensi'])) {
    // Sanitisasi dan validasi input
    $nama = trim($_POST['nama']);
    $jurusan = $_POST['jurusan'];

    if (empty($nama) || empty($jurusan)) {
        $error_message = "Nama dan Jurusan harus diisi!";
    } else {
        // Menggunakan htmlentities untuk mencegah XSS pada data yang disimpan (jika akan ditampilkan lagi)
        $safe_nama = htmlentities($nama, ENT_QUOTES, 'UTF-8');
        $safe_jurusan = htmlentities($jurusan, ENT_QUOTES, 'UTF-8');

        $sql = "INSERT INTO absensi (nama, jurusan, waktu_kehadiran) VALUES (:nama, :jurusan, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nama', $safe_nama);
        $stmt->bindParam(':jurusan', $safe_jurusan);

        if ($stmt->execute()) {
            $success_message = "Absensi berhasil dicatat!";
            // Opsional: Redirect POST-redirect-GET untuk menghindari resubmit
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?status=success_submit");
            exit();
        } else {
            $error_message = "Gagal mencatat absensi!";
        }
    }
}

// Cek status setelah redirect penghapusan
if (isset($_GET['status']) && $_GET['status'] == 'success_delete') {
    $success_message = "Data absensi berhasil dihapus!";
} elseif (isset($_GET['status']) && $_GET['status'] == 'success_submit') {
    $success_message = "Kehadiran Anda berhasil dicatat! Selamat belajar.";
}

// Ambil data absensi
// *** PASTIKAN KOLOM 'id' ADA DI TABEL ANDA ***
$sql = "SELECT id, nama, jurusan, waktu_kehadiran FROM absensi ORDER BY waktu_kehadiran DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$data_absensi = $stmt->fetchAll(); // Menggunakan mode fetch default FETCH_ASSOC

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Digital - Cepat & Akurat</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- Reset dan Font Baru --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            /* Mengganti font */
            background: #f4f7f6;
            /* Warna latar belakang yang lebih lembut */
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* --- Header Baru --- */
        .header {
            text-align: center;
            color: #3b5998;
            /* Warna yang lebih profesional */
            margin-bottom: 40px;
            padding: 20px;
            /* Tambahan untuk efek lebih hidup */
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 4px;
            font-weight: 700;
            color: #3b5998;
        }

        .header p {
            font-size: 16px;
            opacity: 0.8;
            color: #555;
        }

        /* --- Kartu (Card) --- */
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            /* Bayangan yang lebih lembut */
            margin-bottom: 30px;
        }

        h2 {
            color: #3b5998;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: 600;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        /* --- Alert/Pesan --- */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #e6ffed;
            /* Hijau muda */
            color: #1a7e48;
            border: 1px solid #b3e6c9;
        }

        .alert-error {
            background-color: #ffeded;
            /* Merah muda */
            color: #a8323e;
            border: 1px solid #f5c6cb;
        }

        /* --- Form --- */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
            font-size: 14px;
        }

        input[type="text"],
        select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="text"]:focus,
        select:focus {
            outline: none;
            border-color: #3b5998;
            box-shadow: 0 0 0 3px rgba(59, 89, 152, 0.2);
        }

        .btn {
            background: #3b5998;
            /* Biru Primer */
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn:hover {
            background: #4e73b8;
            /* Biru Lebih Cerah */
            transform: translateY(-1px);
        }

        /* --- Tabel & Aksi --- */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            /* Penting untuk border-radius pada tabel */
        }

        th,
        td {
            padding: 15px;
            text-align: left;
        }

        th {
            background-color: #f0f4f8;
            /* Latar belakang header tabel */
            font-weight: 600;
            color: #3b5998;
            text-transform: uppercase;
            font-size: 13px;
            border-bottom: 2px solid #ddd;
        }

        td {
            border-bottom: 1px solid #eee;
            color: #555;
            font-size: 14px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
            /* Selang-seling baris */
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        /* Tombol Hapus */
        .btn-delete {
            background: #e74c3c;
            /* Merah untuk Hapus */
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.2s;
            display: inline-block;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        /* --- Empty State (Tidak Ada Data) --- */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #95a5a6;
            background-color: #f7f7f7;
            border-radius: 8px;
            margin-top: 20px;
        }

        .empty-state-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .empty-state p {
            font-size: 16px;
            font-weight: 400;
        }
    </style>
    <script>
        function konfirmasiHapus(nama) {
            // Menggunakan template literal untuk pesan yang lebih jelas
            return confirm(`Apakah Anda yakin ingin menghapus data absensi atas nama "${nama}"? Tindakan ini tidak dapat dibatalkan.`);
        }
    </script>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Sistem Absensi Digital</h1>
            <p>Platform cepat dan akurat untuk mencatat setiap kehadiran. Disiplin adalah kunci kesuksesan!</p>
        </div>

        <div class="card">
            <h2>📝 Catat Kehadiran Anda Sekarang</h2>
            <p style="margin-bottom: 20px; color: #777; font-size: 14px;">Mohon isi data diri Anda dengan lengkap dan
                benar untuk mencatat waktu kehadiran saat ini.</p>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">✅ <?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">❌ <?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" required placeholder="Contoh: Budi Santoso">
                </div>

                <div class="form-group">
                    <label for="jurusan">Jurusan</label>
                    <select id="jurusan" name="jurusan" required>
                        <option value="">-- Pilih Jurusan Anda --</option>
                        <option value="Teknik Informatika">Teknik Informatika</option>
                        <option value="Sistem Informasi">Sistem Informasi</option>
                        <option value="Teknik Komputer">Teknik Komputer</option>
                        <option value="Manajemen Informatika">Manajemen Informatika</option>
                        <option value="Teknik Elektro">Teknik Elektro</option>
                        <option value="Teknik Mesin">Teknik Mesin</option>
                        <option value="Teknik Sipil">Teknik Sipil</option>
                        <option value="Akuntansi">Akuntansi</option>
                        <option value="Manajemen">Manajemen</option>
                    </select>
                </div>

                <button type="submit" name="submit_absensi" class="btn">🚀 Catat Kehadiran Saya</button>
            </form>
        </div>

        <div class="card">
            <h2>📊 Riwayat Absensi Terbaru</h2>
            <p style="margin-bottom: 20px; color: #777; font-size: 14px;">Berikut adalah rekapitulasi data kehadiran
                yang telah dicatat, diurutkan berdasarkan waktu terbaru.</p>

            <?php if (count($data_absensi) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Jurusan/Departemen</th>
                                <th>Waktu Kehadiran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data_absensi as $index => $row): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($row['jurusan']); ?></td>
                                    <td><?php echo date('d M Y, H:i:s', strtotime($row['waktu_kehadiran'])); ?></td>
                                    <td>
                                        <a href="?action=hapus&id=<?php echo $row['id']; ?>" class="btn-delete"
                                            onclick="return konfirmasiHapus('<?php echo htmlspecialchars($row['nama']); ?>')">
                                            🗑️ Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📂</div>
                    <p>Saat ini **belum ada data absensi** yang tercatat. Silakan masukkan absensi pertama Anda di formulir
                        di atas!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>