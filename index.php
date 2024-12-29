<?php
session_start();

// Jika user sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sistem Peminjaman Ruangan Aula Disdikpora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --light-blue: #f0f7ff;
        }
        body {
            background-color: var(--light-blue);
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), #0099ff);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
        }
        .feature-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .btn-primary {
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
        }
        .footer {
            background: white;
            padding: 2rem 0;
            margin-top: 4rem;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-building"></i>
                Sistem Peminjaman Ruangan Aula Disdikpora
            </a>
            <div class="navbar-nav ms-auto">
                <a class="btn btn-light" href="login.php">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Selamat Datang di Sistem Peminjaman Ruangan Aula Disdikpora</h1>
            <p class="lead mb-4">
                Sistem modern untuk memudahkan proses peminjaman ruangan Aula Disdikpora 
                secara efisien dan terorganisir.
            </p>
            <a href="login.php" class="btn btn-light btn-lg">
                <i class="bi bi-arrow-right-circle"></i> Mulai Sekarang
            </a>
        </div>
    </div>

    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card p-4 text-center">
                    <div class="feature-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h3>Mudah</h3>
                    <p class="text-muted">
                        Proses peminjaman ruangan yang simpel dan cepat dengan antarmuka yang user-friendly.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card p-4 text-center">
                    <div class="feature-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h3>Real-time</h3>
                    <p class="text-muted">
                        Informasi ketersediaan ruangan selalu up-to-date dan dapat diakses kapan saja.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card p-4 text-center">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3>Terpercaya</h3>
                    <p class="text-muted">
                        Sistem manajemen peminjaman yang terorganisir dengan baik dan aman.
                    </p>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-8 mx-auto text-center">
                <h2 class="mb-4">Mengapa Menggunakan Sistem Kami?</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="feature-card p-4">
                            <div class="feature-icon">
                                <i class="bi bi-laptop"></i>
                            </div>
                            <h4>Akses Online</h4>
                            <p class="text-muted">
                                Akses sistem dari mana saja dan kapan saja melalui perangkat Anda.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card p-4">
                            <div class="feature-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <h4>Efisien</h4>
                            <p class="text-muted">
                                Hemat waktu dengan proses peminjaman yang cepat dan transparan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Sistem Peminjaman Ruangan Aula Disdikpora. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 