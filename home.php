<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/install_check.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();

// جلب معلومات الموقع
$stmt = $db->query("SELECT * FROM site_settings LIMIT 1");
$site_info = $stmt->fetch();

// جلب المشاريع
$stmt = $db->query("SELECT * FROM projects ORDER BY display_order ASC, created_at DESC");
$projects = $stmt->fetchAll();

// جلب الفئات
$stmt = $db->query("SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_info['site_title'] ?? 'Portfolio'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site_info['site_description'] ?? ''); ?>">

    <!-- Modern CSS -->
    <link rel="stylesheet" href="assets/css/modern-style.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- AOS Animation -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <!-- Particles Background -->
    <div id="particles-js"></div>

    <!-- Modern Navbar -->
    <nav class="modern-navbar" id="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-terminal"></i>
                <?php echo htmlspecialchars($site_info['site_title'] ?? 'Portfolio'); ?>
            </div>

            <ul class="navbar-menu">
                <li><a href="#home">الرئيسية</a></li>
                <li><a href="#about">عني</a></li>
                <li><a href="#projects">المشاريع</a></li>
                <li><a href="#contact">تواصل معي</a></li>
            </ul>

            <div class="navbar-actions">
                <a href="admin/login.php" class="btn-admin">
                    <i class="fas fa-shield-alt"></i>
                    لوحة التحكم
                </a>
            </div>

            <div class="menu-toggle" id="menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-container" data-aos="fade-up">
            <div class="hero-content">
                <h1>
                    مرحباً، أنا<br>
                    <span class="gradient-text"><?php echo htmlspecialchars($site_info['site_title'] ?? 'Developer'); ?></span>
                </h1>
                <p class="subtitle"><?php echo htmlspecialchars($site_info['site_description'] ?? ''); ?></p>
                <p class="description">
                    <?php echo nl2br(htmlspecialchars($site_info['about_me'] ?? '')); ?>
                </p>

                <div class="hero-actions">
                    <a href="#projects" class="btn-primary">
                        <i class="fas fa-briefcase"></i>
                        استعرض أعمالي
                    </a>
                    <a href="#contact" class="btn-secondary">
                        <i class="fas fa-paper-plane"></i>
                        تواصل معي
                    </a>
                </div>

                <!-- Social Links -->
                <div class="social-links">
                    <?php if (!empty($site_info['github_url'])): ?>
                        <a href="<?php echo htmlspecialchars($site_info['github_url']); ?>" target="_blank" class="social-link">
                            <i class="fab fa-github"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($site_info['linkedin_url'])): ?>
                        <a href="<?php echo htmlspecialchars($site_info['linkedin_url']); ?>" target="_blank" class="social-link">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($site_info['twitter_url'])): ?>
                        <a href="<?php echo htmlspecialchars($site_info['twitter_url']); ?>" target="_blank" class="social-link">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($site_info['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($site_info['email']); ?>" class="social-link">
                            <i class="fas fa-envelope"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hero-image" data-aos="fade-left" data-aos-delay="200">
                <div class="profile-container">
                    <?php if (!empty($site_info['profile_image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($site_info['profile_image']); ?>"
                             alt="Profile" class="profile-img">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/450x450/1a1f3a/00ff41?text=<?php echo urlencode($site_info['site_title'] ?? 'Profile'); ?>"
                             alt="Profile" class="profile-img">
                    <?php endif; ?>
                    <div class="profile-glow"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Projects Section -->
    <section id="projects" class="projects-section">
        <div class="section-header" data-aos="fade-up">
            <h2>مشاريعي <span class="gradient-text">المميزة</span></h2>
            <p>مجموعة من أفضل أعمالي في مجال البرمجة والتطوير</p>
        </div>

        <!-- Project Filters -->
        <?php if (count($categories) > 0): ?>
            <div class="project-filters" data-aos="fade-up" data-aos-delay="100">
                <button class="filter-btn active" data-filter="all">الكل</button>
                <?php foreach ($categories as $category): ?>
                    <button class="filter-btn" data-filter="<?php echo htmlspecialchars($category); ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Projects Grid -->
        <div class="projects-grid">
            <?php if (count($projects) > 0): ?>
                <?php foreach ($projects as $index => $project): ?>
                    <div class="project-card" data-category="<?php echo htmlspecialchars($project['category'] ?? ''); ?>"
                         data-aos="fade-up" data-aos-delay="<?php echo ($index % 3) * 100; ?>">
                        <div class="project-image">
                            <?php if (!empty($project['image'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($project['image']); ?>"
                                     alt="<?php echo htmlspecialchars($project['title']); ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/400x250/1a1f3a/00ff41?text=Project"
                                     alt="<?php echo htmlspecialchars($project['title']); ?>">
                            <?php endif; ?>
                            <div class="project-overlay">
                                <div class="project-quick-view">
                                    <i class="fas fa-eye"></i> معاينة سريعة
                                </div>
                            </div>
                        </div>

                        <div class="project-content">
                            <?php if (!empty($project['category'])): ?>
                                <span class="project-category">
                                    <?php echo htmlspecialchars($project['category']); ?>
                                </span>
                            <?php endif; ?>

                            <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                            <p><?php echo htmlspecialchars(truncateText($project['description'] ?? '', 120)); ?></p>

                            <?php if (!empty($project['technologies'])): ?>
                                <div class="project-tech">
                                    <?php
                                    $techs = explode(',', $project['technologies']);
                                    foreach (array_slice($techs, 0, 4) as $tech):
                                    ?>
                                        <span class="tech-tag"><?php echo htmlspecialchars(trim($tech)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="project-links">
                                <?php if (!empty($project['project_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($project['project_url']); ?>"
                                       target="_blank" class="project-link primary">
                                        <i class="fas fa-external-link-alt"></i>
                                        معاينة
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($project['github_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($project['github_url']); ?>"
                                       target="_blank" class="project-link secondary">
                                        <i class="fab fa-github"></i>
                                        الكود
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-projects" style="grid-column: 1/-1; text-align: center; padding: 4rem;">
                    <i class="fas fa-folder-open" style="font-size: 4rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                    <p style="color: var(--text-secondary); font-size: 1.2rem;">لا توجد مشاريع حالياً</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="section-header" data-aos="fade-up">
            <h2>تواصل <span class="gradient-text">معي</span></h2>
            <p>هل لديك مشروع أو فكرة؟ دعنا نتحدث!</p>
        </div>

        <div class="contact-container" data-aos="fade-up" data-aos-delay="200">
            <form class="contact-form" id="contact-form" method="POST" action="send-message.php">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i> الاسم
                    </label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> البريد الإلكتروني
                    </label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="subject">
                        <i class="fas fa-tag"></i> الموضوع
                    </label>
                    <input type="text" id="subject" name="subject" required>
                </div>

                <div class="form-group">
                    <label for="message">
                        <i class="fas fa-comment"></i> الرسالة
                    </label>
                    <textarea id="message" name="message" required></textarea>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i>
                    إرسال الرسالة
                </button>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_info['site_title'] ?? ''); ?>. جميع الحقوق محفوظة.</p>
    </footer>

    <!-- Scripts -->
    <!-- Particles.js -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

    <!-- AOS Animation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <!-- Custom JS -->
    <script src="assets/js/modern.js"></script>
</body>
</html>
