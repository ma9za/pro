<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();

// جلب معلومات الموقع
$stmt = $db->query("SELECT * FROM site_settings LIMIT 1");
$site_info = $stmt->fetch();

// جلب المشاريع
$stmt = $db->query("SELECT * FROM projects ORDER BY display_order ASC, created_at DESC");
$projects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_info['site_title'] ?? 'موقعي التعريفي'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site_info['site_description'] ?? ''); ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- القائمة العلوية -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h2><?php echo htmlspecialchars($site_info['site_title'] ?? 'موقعي التعريفي'); ?></h2>
            </div>
            <ul class="nav-menu">
                <li><a href="#home">الرئيسية</a></li>
                <li><a href="#about">عني</a></li>
                <li><a href="#portfolio">أعمالي</a></li>
                <li><a href="#contact">تواصل معي</a></li>
                <li><a href="admin/login.php" class="admin-link"><i class="fas fa-lock"></i> الإدارة</a></li>
            </ul>
        </div>
    </nav>

    <!-- قسم البطل -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content">
                <?php if (!empty($site_info['profile_image'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($site_info['profile_image']); ?>"
                         alt="Profile" class="profile-image">
                <?php endif; ?>
                <h1 class="hero-title"><?php echo htmlspecialchars($site_info['full_name'] ?? $site_info['site_title'] ?? 'مرحباً'); ?></h1>
                <p class="hero-subtitle"><?php echo htmlspecialchars($site_info['site_description'] ?? ''); ?></p>
                <div class="social-links">
                    <?php if (!empty($site_info['github_url'])): ?>
                        <a href="<?php echo htmlspecialchars($site_info['github_url']); ?>" target="_blank">
                            <i class="fab fa-github"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($site_info['linkedin_url'])): ?>
                        <a href="<?php echo htmlspecialchars($site_info['linkedin_url']); ?>" target="_blank">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($site_info['twitter_url'])): ?>
                        <a href="<?php echo htmlspecialchars($site_info['twitter_url']); ?>" target="_blank">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- قسم عني -->
    <section id="about" class="about">
        <div class="container">
            <h2 class="section-title">عني</h2>
            <div class="about-content">
                <p><?php echo nl2br(htmlspecialchars($site_info['about_me'] ?? '')); ?></p>
            </div>
        </div>
    </section>

    <!-- قسم الأعمال -->
    <section id="portfolio" class="portfolio">
        <div class="container">
            <h2 class="section-title">أعمالي</h2>
            <div class="projects-grid">
                <?php if (count($projects) > 0): ?>
                    <?php foreach ($projects as $project): ?>
                        <div class="project-card <?php echo $project['is_featured'] ? 'featured' : ''; ?>">
                            <?php if (!empty($project['image'])): ?>
                                <div class="project-image">
                                    <img src="uploads/<?php echo htmlspecialchars($project['image']); ?>"
                                         alt="<?php echo htmlspecialchars($project['title']); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="project-content">
                                <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                                <?php if (!empty($project['category'])): ?>
                                    <span class="project-category"><?php echo htmlspecialchars($project['category']); ?></span>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars(truncateText($project['description'], 150)); ?></p>
                                <?php if (!empty($project['technologies'])): ?>
                                    <div class="project-tech">
                                        <?php
                                        $techs = explode(',', $project['technologies']);
                                        foreach ($techs as $tech):
                                        ?>
                                            <span class="tech-badge"><?php echo htmlspecialchars(trim($tech)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="project-links">
                                    <?php if (!empty($project['project_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($project['project_url']); ?>"
                                           target="_blank" class="btn btn-primary">
                                            <i class="fas fa-external-link-alt"></i> عرض المشروع
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($project['github_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($project['github_url']); ?>"
                                           target="_blank" class="btn btn-secondary">
                                            <i class="fab fa-github"></i> الكود
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-projects">لا توجد أعمال حالياً. قم بإضافة أعمالك من لوحة التحكم.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- قسم التواصل -->
    <section id="contact" class="contact">
        <div class="container">
            <h2 class="section-title">تواصل معي</h2>
            <div class="contact-info">
                <?php if (!empty($site_info['email'])): ?>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?php echo htmlspecialchars($site_info['email']); ?>">
                            <?php echo htmlspecialchars($site_info['email']); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <?php if (!empty($site_info['phone'])): ?>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?php echo htmlspecialchars($site_info['phone']); ?>">
                            <?php echo htmlspecialchars($site_info['phone']); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- التذييل -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_info['site_title'] ?? ''); ?>. جميع الحقوق محفوظة.</p>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>
