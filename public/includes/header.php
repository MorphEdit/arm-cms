<?php
/**
 * Public Header (jQuery Version)
 * ARM CMS - Content Management System
 * 
 * Header สำหรับหน้าสาธารณะ รองรับ responsive design - ใช้ jQuery
 */

// Get current page for navigation

$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = isset($pageTitle) ? $pageTitle : 'ข่าวประชาสัมพันธ์';

// Set current URL for social sharing
$currentUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Set default Open Graph image if not already set
if (!isset($ogImage) || empty($ogImage)) {
    $ogImage = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/assets/images/default-share.jpg';
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - ARM CMS</title>

    <!-- Meta tags for SEO -->
    <meta name="description"
        content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'ข่าวประชาสัมพันธ์ ARM CMS'; ?>">
    <meta name="keywords" content="ข่าว, ประชาสัมพันธ์, ARM CMS">
    <meta name="author" content="ARM CMS">

    <!-- Open Graph tags for Facebook -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description"
        content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'ข่าวประชาสัมพันธ์ ARM CMS'; ?>">
    <meta property="og:type"
        content="<?php echo (strpos($currentPage, 'news.php') !== false) ? 'article' : 'website'; ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="ARM CMS">
    <meta property="og:locale" content="th_TH">

    <!-- Twitter Card tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description"
        content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'ข่าวประชาสัมพันธ์ ARM CMS'; ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    <meta name="twitter:site" content="@ARM_CMS">
    <meta name="twitter:creator" content="@ARM_CMS">

    <!-- LINE sharing tags -->
    <meta property="line:card" content="summary">

    <!-- Additional article meta for news pages -->
    <?php if (strpos($currentPage, 'news.php') !== false && isset($newsData)): ?>
        <meta property="article:published_time" content="<?php echo date('c', strtotime($newsData['created_at'])); ?>">
        <meta property="article:modified_time" content="<?php echo date('c', strtotime($newsData['updated_at'])); ?>">
        <meta property="article:section" content="<?php echo htmlspecialchars($newsData['category']); ?>">
        <meta property="article:tag" content="<?php echo htmlspecialchars($newsData['category']); ?>">
        <meta property="article:author" content="ARM CMS">
    <?php endif; ?>

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($currentUrl); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="../assets/images/apple-touch-icon.png">

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <!-- Google Fonts (optional) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- jQuery CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- Structured Data for better SEO -->
    <?php if (strpos($currentPage, 'news.php') !== false && isset($newsData)): ?>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "NewsArticle",
            "headline": "<?php echo addslashes(htmlspecialchars($newsData['title'])); ?>",
            "description": "<?php echo addslashes(htmlspecialchars($pageDescription)); ?>",
            "image": "<?php echo htmlspecialchars($ogImage); ?>",
            "datePublished": "<?php echo date('c', strtotime($newsData['created_at'])); ?>",
            "dateModified": "<?php echo date('c', strtotime($newsData['updated_at'])); ?>",
            "author": {
                "@type": "Organization",
                "name": "ARM CMS"
            },
            "publisher": {
                "@type": "Organization",
                "name": "ARM CMS",
                "logo": {
                    "@type": "ImageObject",
                    "url": "<?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/assets/images/logo.png'; ?>"
                }
            },
            "mainEntityOfPage": {
                "@type": "WebPage",
                "@id": "<?php echo htmlspecialchars($currentUrl); ?>"
            }
        }
        </script>
    <?php endif; ?>

    <!-- Additional head content -->
    <?php if (isset($additionalHead)): ?>
        <?php echo $additionalHead; ?>
    <?php endif; ?>
</head>

<body class="public-layout">

    <!-- Skip to main content (accessibility) -->
    <a href="#main-content" class="skip-link">ข้ามไปยังเนื้อหาหลัก</a>

    <!-- Header -->
    <header class="public-header">
        <div class="container">
            <div class="header-content">

                <!-- Logo/Site Title -->
                <div class="site-brand">
                    <a href="index.php" class="brand-link">
                        <h1 class="site-title">ARM CMS</h1>
                        <p class="site-subtitle">ระบบข่าวประชาสัมพันธ์</p>
                    </a>
                </div>

                <!-- Navigation -->
                <!-- <nav class="main-nav" role="navigation" aria-label="เมนูหลัก">
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="index.php" 
                               class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>"
                               <?php echo $currentPage === 'index.php' ? 'aria-current="page"' : ''; ?>>
                                <i class="bi bi-house"></i> หน้าหลัก
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?category=ทั่วไป" 
                               class="nav-link category-link"
                               data-category="ทั่วไป">
                                <i class="bi bi-layout-text-sidebar-reverse"></i> ข่าวทั่วไป
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?category=ประกาศ" 
                               class="nav-link category-link"
                               data-category="ประกาศ">
                                <i class="bi bi-megaphone"></i> ประกาศ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="index.php?category=กิจกรรม" 
                               class="nav-link category-link"
                               data-category="กิจกรรม">
                                <i class="bi bi-magic"></i> กิจกรรม
                            </a>
                        </li>
                    </ul>

                    Mobile Menu Toggle
                    <button class="mobile-menu-toggle" aria-label="เปิด/ปิดเมนู" aria-expanded="false" id="mobileMenuToggle">
                        <span class="hamburger"></span>
                        <span class="hamburger"></span>
                        <span class="hamburger"></span>
                    </button>
                </nav> -->

                <!-- Admin Link (if needed) -->
                <div class="header-actions">
                    <a href="../index.php" class="admin-link" title="เข้าสู่ระบบจัดการ">
                        <i class="bi bi-gear"></i> จัดการ
                    </a>

                    <a href="logout.php" class="admin-link" title="ออกจากระบบ">
                        <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                    </a>
                </div>

            </div>
        </div>
    </header>

    <!-- Mobile Navigation Overlay -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>

    <!-- Main Content Start -->
    <main id="main-content" class="main-content" role="main">

        <!-- Breadcrumb (if provided) -->
        <?php if (isset($breadcrumb) && !empty($breadcrumb)): ?>
            <div class="container">
                <nav class="breadcrumb" aria-label="breadcrumb">
                    <ol class="breadcrumb-list">
                        <?php foreach ($breadcrumb as $index => $item): ?>
                            <li class="breadcrumb-item <?php echo $index === count($breadcrumb) - 1 ? 'active' : ''; ?>">
                                <?php if ($index === count($breadcrumb) - 1): ?>
                                    <span aria-current="page"><?php echo htmlspecialchars($item['title']); ?></span>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($item['url']); ?>">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            </div>
        <?php endif; ?>

<script>
/**
 * Mobile menu functionality (jQuery Version)
 * การทำงาน: จัดการเมนูมือถือ เปิด/ปิด และปรับ aria attributes
 */

$(document).ready(function() {
    
    // Mobile menu toggle
    $('#mobileMenuToggle').on('click', function() {
        toggleMobileMenu();
    });
    
    // Mobile overlay click
    $('#mobileNavOverlay').on('click', function() {
        closeMobileMenu();
    });
    
    // Close menu when clicking on nav links (mobile)
    $('.nav-link').on('click', function() {
        if ($(window).width() <= 768) {
            closeMobileMenu();
        }
    });
    
    // Handle window resize
    $(window).on('resize', function() {
        if ($(window).width() > 768) {
            closeMobileMenu();
        }
    });
    
    // Close menu on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
        }
    });
    
    // Set active category link based on URL
    setActiveCategoryLink();
    
    // Add smooth hover effects
    $('.nav-link').on('mouseenter', function() {
        $(this).addClass('nav-hover');
    }).on('mouseleave', function() {
        $(this).removeClass('nav-hover');
    });
    
    // Add click animations
    $('.nav-link').on('click', function() {
        $(this).addClass('nav-clicked');
        setTimeout(() => {
            $(this).removeClass('nav-clicked');
        }, 200);
    });
    
});

function toggleMobileMenu() {
    const $nav = $('.main-nav');
    const $overlay = $('#mobileNavOverlay');
    const $toggle = $('#mobileMenuToggle');
    const $body = $('body');

    const isOpen = $nav.hasClass('mobile-open');

    if (isOpen) {
        closeMobileMenu();
    } else {
        openMobileMenu();
    }
}

function openMobileMenu() {
    const $nav = $('.main-nav');
    const $overlay = $('#mobileNavOverlay');
    const $toggle = $('#mobileMenuToggle');
    const $body = $('body');

    $nav.addClass('mobile-open');
    $overlay.addClass('active');
    $toggle.attr('aria-expanded', 'true').addClass('active');
    $body.addClass('mobile-menu-open');

    // Focus management
    const $firstLink = $nav.find('.nav-link').first();
    if ($firstLink.length) {
        $firstLink.focus();
    }
    
    // Add animation delay for each nav item
    $('.nav-item').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
    });
}

function closeMobileMenu() {
    const $nav = $('.main-nav');
    const $overlay = $('#mobileNavOverlay');
    const $toggle = $('#mobileMenuToggle');
    const $body = $('body');

    $nav.removeClass('mobile-open');
    $overlay.removeClass('active');
    $toggle.attr('aria-expanded', 'false').removeClass('active');
    $body.removeClass('mobile-menu-open');
    
    // Remove animation delays
    $('.nav-item').css('animation-delay', '');
}

/**
 * Set active category link based on URL parameters
 * การทำงาน: ตั้งค่า active link ตาม URL parameter
 */
function setActiveCategoryLink() {
    const urlParams = new URLSearchParams(window.location.search);
    const category = urlParams.get('category');
    
    // Remove all active classes from category links
    $('.category-link').removeClass('active');
    
    if (category) {
        // Add active class to matching category link
        $(`.category-link[data-category="${category}"]`).addClass('active');
        
        // Remove active from home link if we're on a category page
        $('a[href="index.php"]').removeClass('active');
    } else if (window.location.pathname.endsWith('index.php')) {
        // Add active to home link if no category and on index page
        $('a[href="index.php"]').addClass('active');
    }
}

/**
 * Handle smooth page transitions
 * การทำงาน: เพิ่ม loading state ระหว่างการเปลี่ยนหน้า
 */
$('.nav-link').on('click', function(e) {
    const href = $(this).attr('href');
    
    // Don't add loading for external links or anchors
    if (href && !href.startsWith('http') && !href.startsWith('#')) {
        // Add loading state
        showPageTransition();
        
        // Small delay for better UX
        setTimeout(() => {
            window.location.href = href;
        }, 200);
        
        e.preventDefault();
    }
});

/**
 * Show page transition loading
 * การทำงาน: แสดง loading ระหว่างเปลี่ยนหน้า
 */
function showPageTransition() {
    if (typeof showGlobalLoading === 'function') {
        showGlobalLoading('กำลังโหลดหน้าใหม่...');
    } else {
        // Fallback if global loading not available
        $('body').addClass('page-transitioning');
    }
}

/**
 * Add search functionality to header (if search box exists)
 * การทำงาน: เพิ่มฟังก์ชันค้นหาใน header
 */
if ($('.header-search').length) {
    $('.header-search input').on('keypress', function(e) {
        if (e.key === 'Enter') {
            const searchTerm = $(this).val().trim();
            if (searchTerm) {
                window.location.href = `index.php?search=${encodeURIComponent(searchTerm)}`;
            }
        }
    });
    
    $('.header-search button').on('click', function() {
        const searchTerm = $('.header-search input').val().trim();
        if (searchTerm) {
            window.location.href = `index.php?search=${encodeURIComponent(searchTerm)}`;
        }
    });
}

/**
 * Add notification banner functionality (if needed)
 * การทำงาน: จัดการ notification banner
 */
$('.notification-banner .close-btn').on('click', function() {
    $(this).closest('.notification-banner').slideUp(300);
    
    // Store in localStorage to remember dismissal
    localStorage.setItem('notificationDismissed', 'true');
});

// Check if notification was previously dismissed
if (localStorage.getItem('notificationDismissed') === 'true') {
    $('.notification-banner').hide();
}

/**
 * Add header scroll effect
 * การทำงาน: เพิ่มเอฟเฟกต์เมื่อเลื่อนหน้า
 */
$(window).on('scroll', function() {
    const $header = $('.public-header');
    if ($(window).scrollTop() > 100) {
        $header.addClass('scrolled');
    } else {
        $header.removeClass('scrolled');
    }
});

/**
 * Enhance accessibility
 * การทำงาน: ปรับปรุง accessibility
 */
$('.nav-link').on('focus', function() {
    $(this).addClass('focus-visible');
}).on('blur', function() {
    $(this).removeClass('focus-visible');
});

// Skip link functionality
$('.skip-link').on('click', function(e) {
    e.preventDefault();
    $('#main-content').attr('tabindex', '-1').focus();
});

</script>

<style>
/* Additional header styles for jQuery enhancements */
.nav-link.nav-hover {
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

.nav-link.nav-clicked {
    transform: scale(0.95);
    transition: transform 0.1s ease;
}

.mobile-menu-toggle.active .hamburger:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.mobile-menu-toggle.active .hamburger:nth-child(2) {
    opacity: 0;
}

.mobile-menu-toggle.active .hamburger:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

.main-nav.mobile-open .nav-item {
    animation: slideInRight 0.3s ease forwards;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.public-header.scrolled {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
}

.nav-link.focus-visible {
    outline: 2px solid rgb(224, 6, 42);
    outline-offset: 2px;
}

.page-transitioning {
    pointer-events: none;
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.skip-link {
    position: absolute;
    top: -40px;
    left: 6px;
    background: rgb(224, 6, 42);
    color: white;
    padding: 8px 16px;
    text-decoration: none;
    border-radius: 4px;
    z-index: 1000;
    transition: top 0.3s;
}

.skip-link:focus {
    top: 6px;
}
</style>