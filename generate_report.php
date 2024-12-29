<?php
ob_start(); // Start output buffering
require_once 'config/database.php';
require_once 'vendor/autoload.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Check if period is provided
if (!isset($_GET['period'])) {
    die('Periode laporan tidak ditemukan');
}

$period = $_GET['period'];
$today = date('Y-m-d');

try {
    // Set date range based on period
    switch($period) {
        case 'week':
            $start_date = date('Y-m-d', strtotime('monday this week'));
            $end_date = date('Y-m-d', strtotime('sunday this week'));
            $period_text = 'Minggu Ini (' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)) . ')';
            break;
        case 'month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            $period_text = 'Bulan ' . date('F Y');
            break;
        case 'year':
            $start_date = date('Y-01-01');
            $end_date = date('Y-12-31');
            $period_text = 'Tahun ' . date('Y');
            break;
        default:
            die('Periode tidak valid');
    }

    // Get bookings for the period
    $stmt = $db->prepare("
        SELECT p.*, u.username, u.nama_lengkap, u.no_whatsapp, u.pekerjaan,
               r.nama_ruangan, r.kapasitas, r.fasilitas
        FROM peminjaman p
        JOIN users u ON p.user_id = u.id
        JOIN ruangan r ON p.ruangan_id = r.id
        WHERE p.tanggal_mulai BETWEEN ? AND ?
        ORDER BY p.tanggal_mulai ASC, p.tanggal_selesai ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create new PDF document
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Sistem Peminjaman Ruangan');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Laporan Peminjaman Ruangan');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // Add content
    $html = '
    <h1 style="text-align: center;">LAPORAN PEMINJAMAN RUANGAN</h1>
    <h3 style="text-align: center;">' . $period_text . '</h3>
    <br><br>
    <table border="1" cellpadding="5">
        <tr style="background-color: #f5f5f5;">
            <th width="5%"><strong>No</strong></th>
            <th width="20%"><strong>Peminjam</strong></th>
            <th width="15%"><strong>Ruangan</strong></th>
            <th width="15%"><strong>Tanggal</strong></th>
            <th width="15%"><strong>Waktu</strong></th>
            <th width="15%"><strong>Status</strong></th>
            <th width="15%"><strong>Keperluan</strong></th>
        </tr>';

    $no = 1;
    foreach ($bookings as $booking) {
        $status_color = $booking['status_peminjaman'] === 'disetujui' ? 'green' : 
                       ($booking['status_peminjaman'] === 'ditolak' ? 'red' : 'orange');
        
        $html .= '
        <tr>
            <td>' . $no++ . '</td>
            <td>' . htmlspecialchars($booking['nama_lengkap']) . '<br>
                <small>' . htmlspecialchars($booking['no_whatsapp']) . '</small></td>
            <td>' . htmlspecialchars($booking['nama_ruangan']) . '</td>
            <td>' . date('d/m/Y', strtotime($booking['tanggal_mulai'])) . '</td>
            <td>' . date('H:i', strtotime($booking['tanggal_mulai'])) . ' - ' . 
                    date('H:i', strtotime($booking['tanggal_selesai'])) . '</td>
            <td style="color: ' . $status_color . '">' . ucfirst($booking['status_peminjaman']) . '</td>
            <td>' . htmlspecialchars($booking['keperluan']) . '</td>
        </tr>';
    }

    if (empty($bookings)) {
        $html .= '<tr><td colspan="7" style="text-align: center;">Tidak ada data peminjaman</td></tr>';
    }

    $html .= '
    </table>
    <br><br><br>
    <table>
        <tr>
            <td width="60%"></td>
            <td width="40%" style="text-align: center;">
                ' . date('d F Y') . '<br>
                Petugas<br><br><br><br>
                Wisnoe Ari Wibowo<br>
                NIP.199109092020121002
            </td>
        </tr>
    </table>
    ';

    // Print content
    $pdf->writeHTML($html, true, false, true, false, '');

    // Clean any output buffers
    ob_end_clean();

    // Close and output PDF document
    $pdf->Output('laporan_peminjaman_' . $period . '_' . date('Y-m-d') . '.pdf', 'I');

} catch (Exception $e) {
    die('Terjadi kesalahan: ' . $e->getMessage());
} 