<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mutai Enterprises Limited - Fleet Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #2C3E50;
            --secondary-color: #3498DB;
            --accent-color: #E74C3C;
            --text-color: #2C3E50;
            --light-bg: #F8F9FA;
            --white: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            line-height: 1.6;
            color: var(--text-color);
        }

        /* Header Styles */
        header {
            background-color: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--secondary-color);
        }

        .login-btn {
            background-color: var(--secondary-color);
            color: var(--white);
            padding: 0.5rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .login-btn:hover {
            background-color: #2980B9;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(52, 152, 219, 0.9)), url('truck.jpg');
            background-size: cover;
            background-position: center;
            color: var(--white);
            padding: 8rem 2rem 4rem;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto 2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .cta-btn {
            padding: 0.8rem 2rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .primary-btn {
            background-color: var(--white);
            color: var(--primary-color);
        }

        .secondary-btn {
            background-color: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }

        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Features Section */
        .features {
            padding: 4rem 2rem;
            background-color: var(--light-bg);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background-color: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        /* Stats Section */
        .stats {
            padding: 4rem 2rem;
            background-color: var(--primary-color);
            color: var(--white);
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item {
            padding: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }

        /* Footer */
        footer {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 3rem 2rem;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
        }

        .footer-links a {
            color: var(--white);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--secondary-color);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .social-links a {
            color: var(--white);
            font-size: 1.5rem;
            transition: color 0.3s;
        }

        .social-links a:hover {
            color: var(--secondary-color);
        }

        .copyright {
            margin-top: 2rem;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="nav-container">
            <div class="logo-container">
                <img src="logo.png" alt="Company Logo" class="logo-img">
                <a href="index.php" class="logo">Mutai Enterprises Limited</a>
            </div>
            <div class="nav-links">
                <a href="#features">Features</a>
                
                <a href="contact.php">Contact</a>
                <a href="login.php" class="login-btn">Login</a>
            </div>
        </nav>
        <div class="notification-container">
            <div class="notification-bell" onclick="toggleMaintenanceNotifications()">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="maintenanceNotificationCount">0</span>
            </div>
        </div>
    </header>

    <section class="hero">
        <h1>Fleet Maintenance Management System</h1>
        <p>Streamline your fleet operations with our comprehensive management system. Track vehicles, manage maintenance, and optimize your fleet's performance.</p>
        <div class="cta-buttons">
            <a href="login.php" class="cta-btn primary-btn">Get Started</a>
            <a href="#features" class="cta-btn secondary-btn">Learn More</a>
        </div>
    </section>

    <section class="features" id="features">
        <div class="features-container">
            <div class="section-title">
                <h2>Key Features</h2>
                <p>Everything you need to manage your fleet efficiently</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-truck feature-icon"></i>
                    <h3>Vehicle Management</h3>
                    <p>Track all your vehicles, their status, and maintenance schedules in one place.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-tools feature-icon"></i>
                    <h3>Maintenance Tracking</h3>
                    <p>Schedule and monitor maintenance tasks, ensuring your fleet stays in top condition.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-user-tie feature-icon"></i>
                    <h3>Driver Management</h3>
                    <p>Manage driver assignment and maintain driver records.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-calendar-check feature-icon"></i>
                    <h3>Maintenance Scheduling</h3>
                    <p>Easily schedule and track maintenance appointments for your entire fleet.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-bell feature-icon"></i>
                    <h3>Message Notifications</h3>
                    <p>Stay alerted for any important messages from the admin.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-line feature-icon"></i>
                    <h3>Performance Analytics</h3>
                    <p>Monitor fleet performance and make data-driven decisions.</p>
                </div>
            </div>
        </div>
    </section>

    <div id="maintenanceNotificationModal" class="modal">
        <div class="modal-content">
            ...
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="#features">Features</a>
                
                <a href="contact.php">Contact</a>
                <a href="login.php">Login</a>
            </div>
            
            <div class="copyright">
                <p>&copy; 2025 Mutai Enterprises Limited. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
    function toggleMaintenanceNotifications() {
        // Your logic here, or just a placeholder
        alert('Maintenance notifications toggled!');
    }

    // Add this to suppress extension-related errors
    window.addEventListener('error', function(e) {
        if (e.message.includes('runtime.lastError') || e.message.includes('extension port')) {
            e.preventDefault();
            return false;
        }
    });
    </script>
</body>
</html> 