<?php
session_start();

// Check if user is already logged in and redirect accordingly
if (isset($_SESSION['admin_id'])) 
{
    header("Location: admin/admin_dashboard.php");
    exit();
} 
elseif (isset($_SESSION['user_id'])) 
{
    header("Location: user/user_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sri Balathandayuthapani Temple - Welcome</title>
    <meta name="description" content="Welcome to Sri Balathandayuthapani Temple - Online booking, donations, and event management">
    <style>
        * 
        {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body 
        {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navigation Bar */
        .navbar 
        {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .navbar-container 
        {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo 
        {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: bold;
            color: #764ba2;
        }

        .logo-icon 
        {
            font-size: 36px;
        }

        .nav-links 
        {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a 
        {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover 
        {
            color: #764ba2;
        }

        .login-btn 
        {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .login-btn:hover 
        {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
        }

        .login-dropdown 
        {
            position: relative;
        }

        .dropdown-content 
        {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            margin-top: 10px;
            z-index: 999;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s, transform 0.3s;
        }

        .login-dropdown.active .dropdown-content 
        {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-content a 
        {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            transition: background 0.3s;
        }

        .dropdown-content a:hover 
        {
            background: #f0f0f0;
        }

        /* Hero Section */
        .hero 
        {
            margin-top: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
        }

        .hero h1 
        {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .hero p 
        {
            font-size: 20px;
            margin-bottom: 30px;
        }

        .hero-buttons 
        {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn 
        {
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary 
        {
            background: white;
            color: #764ba2;
        }

        .btn-primary:hover 
        {
            transform: translateY(-3px);
        }

        .btn-secondary 
        {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover 
        {
            background: white;
            color: #764ba2;
        }

        /* User Type Cards */
        .user-types 
        {
            padding: 80px 20px;
            background: #f8f9fa;
        }

        .section-title 
        {
            text-align: center;
            font-size: 36px;
            color: #333;
            margin-bottom: 20px;
        }

        .section-subtitle 
        {
            text-align: center;
            color: #666;
            margin-bottom: 50px;
            font-size: 18px;
        }

        .cards-container 
        {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 0 20px;
        }

        .user-card 
        {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .user-card:hover 
        {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .card-icon 
        {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .user-card h3 
        {
            font-size: 24px;
            color: #764ba2;
            margin-bottom: 15px;
        }

        .user-card p 
        {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .user-card .btn 
        {
            width: 100%;
            display: block;
            text-align: center;
        }

        /* Features Section */
        .features 
        {
            padding: 80px 20px;
            background: white;
        }

        .features-grid 
        {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            padding: 0 20px;
        }

        .feature-item 
        {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .feature-icon
        {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .feature-item h4 {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
        }

        .feature-item p 
        {
            color: #666;
            line-height: 1.6;
            max-width: 250px;
        }

        /* Events Preview */
        .events-preview 
        {
            padding: 80px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .events-grid 
        {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 0 20px;
        }

        .event-card 
        {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .event-date 
        {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 10px;
        }

        .event-card h4 
        {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .event-card p 
        {
            opacity: 0.9;
            line-height: 1.6;
        }

        /* Footer */
        .footer 
        {
            background: #2c3e50;
            color: white;
            padding: 50px 20px 20px;
        }

        .footer-content 
        {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
        }

        .footer-section h3 
        {
            margin-bottom: 20px;
            color: #fff;
        }

        .footer-section p,
        .footer-section a 
        {
            color: #bdc3c7;
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            transition: color 0.3s;
        }

        .footer-section a:hover 
        {
            color: #fff;
        }

        .footer-bottom 
        {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #34495e;
            color: #bdc3c7;
        }

        /* Animations */
        @keyframes fadeInUp 
        {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile Menu */
        .mobile-menu-btn 
        {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }

        /* Responsive */
        @media (max-width: 768px) 
        {
            .nav-links 
            {
                display: none;
            }

            .mobile-menu-btn 
            {
                display: block;
            }

            .hero h1 
            {
                font-size: 32px;
            }

            .hero p 
            {
                font-size: 16px;
            }

            .hero-buttons 
            {
                flex-direction: column;
            }

            .section-title 
            {
                font-size: 28px;
            }

            .cards-container 
            {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">
                <span class="logo-icon">🕉️</span>
                <span>Sri Balathandayuthapani</span>
            </div>
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="#services">Services</a>
                <a href="#events">Events</a>
                <a href="#contact">Contact</a>
                <div class="login-dropdown">
                    <button class="login-btn">Login / Register</button>
                    <div class="dropdown-content">
                        <a href="devotee/devotee_login.php">👤 Devotee Login</a>
                        <a href="devotee/devotee_register.php">📝 Register</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>🕉️ Welcome to Sri Balathandayuthapani Temple</h1>
            <p>Experience divine blessings through our online booking system. Book poojas, make donations, and register for events - all from the comfort of your home.</p>
            <div class="hero-buttons">
                <a href="devotee/devotee_register.php" class="btn btn-primary">Get Started</a>
                <a href="#services" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- User Type Cards -->
    <section class="user-types" id="services">
        <h2 class="section-title">Choose Your Portal</h2>
        <p class="section-subtitle">Access the system based on your role</p>
        
        <div class="cards-container">
            <!-- Admin Card -->
            <div class="user-card">
                <div class="card-icon">👨‍💼</div>
                <h3>Admin Portal</h3>
                <p>Manage temple operations, bookings, donations, events, and generate reports. Full control over the temple management system.</p>
                <a href="admin/admin_login.php" class="btn btn-primary">Admin Login</a>
            </div>

            <!-- Priest Card -->
            <div class="user-card">
                <div class="card-icon">🕉️</div>
                <h3>Priest Portal</h3>
                <p>View assigned bookings, manage your schedule, update service status, and communicate with devotees.</p>
                <a href="priest/priest_login.php" class="btn btn-primary">Priest Login</a>
            </div>

            <!-- Devotee/User Card -->
            <div class="user-card">
                <div class="card-icon">🙏</div>
                <h3>Devotee Portal</h3>
                <p>Book wedding ceremonies, priest services, make donations, register for events, and manage your bookings online.</p>
                <a href="devotee/devotee_login.php" class="btn btn-primary">Devotee Login</a>
            </div>

            <!-- Guest/Visitor Card -->
            <div class="user-card">
                <div class="card-icon">👥</div>
                <h3>Guest Portal</h3>
                <p>View upcoming events, temple timings, donation information, and explore our services without registration.</p>
                <a href="#events" class="btn btn-primary">Browse Events</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <h2 class="section-title">Our Services</h2>
        <p class="section-subtitle">Everything you need for your spiritual journey</p>
        
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">💍</div>
                <h4>Wedding Bookings</h4>
                <p>Book auspicious dates for your wedding ceremonies with our easy online booking system.</p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">🙏</div>
                <h4>Priest Services</h4>
                <p>Request priest services for home poojas, housewarming, and other religious ceremonies.</p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">💰</div>
                <h4>Online Donations</h4>
                <p>Make secure donations for temple maintenance, festivals, and charitable activities.</p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">🎉</div>
                <h4>Event Registration</h4>
                <p>Register for upcoming festivals, cultural programs, and community events.</p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">📅</div>
                <h4>Calendar View</h4>
                <p>View all temple events, festivals, and available dates in an interactive calendar.</p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">🧾</div>
                <h4>Digital Receipts</h4>
                <p>Receive instant digital receipts for all your donations and bookings via email.</p>
            </div>
        </div>
    </section>

    <!-- Upcoming Events Preview -->
    <section class="events-preview" id="events">
        <h2 class="section-title">Upcoming Events</h2>
        <p class="section-subtitle">Join us for these divine celebrations</p>
        
        <div class="events-grid">
            <div class="event-card">
                <div class="event-date">📅 January 14, 2026</div>
                <h4>Pongal Celebration</h4>
                <p>Join us for the traditional Tamil harvest festival with special prayers and cultural programs.</p>
            </div>

            <div class="event-card">
                <div class="event-date">📅 January 23, 2026</div>
                <h4>Thai Poosam Kavadi</h4>
                <p>Grand Thaipusam celebration with kavadi procession and special abhishekam for Lord Murugan.</p>
            </div>

            <div class="event-card">
                <div class="event-date">📅 February 17, 2026</div>
                <h4>Maha Shivaratri</h4>
                <p>All-night vigil celebrating the great night of Lord Shiva with continuous prayers and offerings.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>🕉️ Sri Balathandayuthapani Temple</h3>
                <p>Dedicated to spreading divine blessings and preserving Tamil Hindu culture in the community.</p>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="#home">Home</a>
                <a href="#services">Services</a>
                <a href="#events">Events</a>
                <a href="#contact">Contact Us</a>
            </div>

            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>📍 Sri Balathandayuthapani Temple, 139A, Jalan Yam Tuan, Bandar Seremban, 70000 Seremban, Negeri Sembilan</p>
                <p>📞 +60 12-345-6789</p>
                <p>✉️ info@balathandayuthapani.com</p>
            </div>

            <div class="footer-section">
                <h3>Temple Timings</h3>
                <p>🌅 Morning: 6:00 AM - 12:00 PM</p>
                <p>🌆 Evening: 5:00 PM - 9:00 PM</p>
                <p>📅 Open Daily</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 Sri Balathandayuthapani Temple. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => 
        {
            anchor.addEventListener('click', function (e) 
            {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) 
                {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Click-based dropdown menu (SOLUTION 2)
        document.addEventListener('DOMContentLoaded', function() 
        {
            const loginBtn = document.querySelector('.login-btn');
            const dropdown = document.querySelector('.login-dropdown');
            
            if (loginBtn && dropdown) 
            {
                // Toggle dropdown on button click
                loginBtn.addEventListener('click', function(e) 
                {
                    e.stopPropagation();
                    dropdown.classList.toggle('active');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) 
                {
                    if (!dropdown.contains(e.target)) 
                    {
                        dropdown.classList.remove('active');
                    }
                });
                
                // Prevent dropdown from closing when clicking inside it
                dropdown.addEventListener('click', function(e) 
                {
                    e.stopPropagation();
                });
            }
        });

        // Mobile menu toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        if (mobileMenuBtn) 
        {
            mobileMenuBtn.addEventListener('click', function() 
            {
                const navLinks = document.querySelector('.nav-links');
                navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
            });
        }
    </script>
</body>
</html>