<?php
ob_start(); // Start output buffering
require_once 'config/database.php';
require_once 'vendor/autoload.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    die('ID peminjaman tidak ditemukan');
}

$booking_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // Get booking details with user and room info
    $stmt = $db->prepare("
        SELECT p.*, u.username, u.nama_lengkap, u.no_whatsapp, u.pekerjaan,
               r.nama_ruangan, r.kapasitas, r.fasilitas
        FROM peminjaman p
        JOIN users u ON p.user_id = u.id
        JOIN ruangan r ON p.ruangan_id = r.id
        WHERE p.id = ? AND p.user_id = ? AND p.status_peminjaman = 'disetujui'
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        die('Peminjaman tidak ditemukan atau tidak dapat dicetak');
    }

    // Create new PDF document using the full namespace
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Sistem Peminjaman Ruangan');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Bukti Peminjaman Ruangan');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // Add content
    $html = '
    <h1 style="text-align: center;">BUKTI PEMINJAMAN RUANGAN</h1>
    <br><br>
    <table cellpadding="5">
        <tr>
            <td width="30%"><strong>Nomor Peminjaman</strong></td>
            <td width="70%">: ' . sprintf('BOOK-%05d', $booking['id']) . '</td>
        </tr>
        <tr>
            <td><strong>Tanggal Cetak</strong></td>
            <td>: ' . date('d/m/Y H:i') . '</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>: DISETUJUI</td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr>
            <td><strong>Nama Peminjam</strong></td>
            <td>: ' . htmlspecialchars($booking['nama_lengkap']) . '</td>
        </tr>
        <tr>
            <td><strong>Pekerjaan</strong></td>
            <td>: ' . htmlspecialchars($booking['pekerjaan']) . '</td>
        </tr>
        <tr>
            <td><strong>WhatsApp</strong></td>
            <td>: ' . htmlspecialchars($booking['no_whatsapp']) . '</td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr>
            <td><strong>Ruangan</strong></td>
            <td>: ' . htmlspecialchars($booking['nama_ruangan']) . '</td>
        </tr>
        <tr>
            <td><strong>Tanggal</strong></td>
            <td>: ' . date('d/m/Y', strtotime($booking['tanggal_mulai'])) . '</td>
        </tr>
        <tr>
            <td><strong>Waktu</strong></td>
            <td>: ' . date('H:i', strtotime($booking['tanggal_mulai'])) . ' - ' . 
                     date('H:i', strtotime($booking['tanggal_selesai'])) . ' WIB</td>
        </tr>
        <tr>
            <td><strong>Keperluan</strong></td>
            <td>: ' . htmlspecialchars($booking['keperluan']) . '</td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr>
            <td><strong>Kapasitas Ruangan</strong></td>
            <td>: ' . htmlspecialchars($booking['kapasitas']) . ' orang</td>
        </tr>
        <tr>
            <td><strong>Fasilitas</strong></td>
            <td>: ' . htmlspecialchars($booking['fasilitas']) . '</td>
        </tr>
    </table>
    <br><br><br>
    <table>
        <tr>
            <td width="60%"></td>
            <td width="40%" style="text-align: center;">
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
    $pdf->Output('bukti_peminjaman_' . sprintf('%05d', $booking['id']) . '.pdf', 'I');

} catch (Exception $e) {
    die('Terjadi kesalahan: ' . $e->getMessage());
} 