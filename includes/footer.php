<?php
$footerSettings = [
    'museum_name' => 'Bảo Tàng Văn Hóa Dân Tộc Khmer',
    'description' => 'Đưa bảo tàng lên không gian mạng chính là mở rộng cánh cửa trái tim văn hóa đến với mọi miền Tổ quốc và bạn bè quốc tế.',
    'facebook_url' => '',
    'youtube_url' => '',
    'opening_days' => 'Thứ hai - Chủ nhật',
    'opening_hours' => '07:00 - 11:00, 13:00 - 17:00',
    'address' => 'Nguyễn Du, khóm 3, phường Nguyệt Hóa, tỉnh Vĩnh Long',
    'map_url' => 'https://www.google.com/maps?q=B%E1%BA%A3o%20t%C3%A0ng%20V%C4%83n%20h%C3%B3a%20D%C3%A2n%20t%E1%BB%99c%20Khmer%20t%E1%BB%89nh%20V%C4%A9nh%20Long&output=embed',
    'phone' => '0294.3842.188',
    'email' => 'baotangvhdtkhmer@gmail.com',
    'website' => 'https://baotangkhmer.free.je/?i=1',
    'copyright' => '© 2026 Bảo tàng Văn hóa Dân tộc Khmer tỉnh Vĩnh Long.',
];

if (isset($conn) && $conn instanceof mysqli) {
    $settingsTable = $conn->query("SHOW TABLES LIKE 'site_settings'");
    if ($settingsTable && $settingsTable->num_rows > 0) {
        $settingsResult = $conn->query('SELECT setting_key, setting_value FROM site_settings');
        if (!$settingsResult) {
            error_log('Footer settings query failed: ' . $conn->error);
        } else {
            while ($setting = $settingsResult->fetch_assoc()) {
                if (array_key_exists($setting['setting_key'], $footerSettings)) {
                    $footerSettings[$setting['setting_key']] = $setting['setting_value'];
                }
            }
        }
    }
}

$footerEscape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$facebookUrl = filter_var($footerSettings['facebook_url'], FILTER_VALIDATE_URL)
    && in_array(strtolower((string) parse_url($footerSettings['facebook_url'], PHP_URL_SCHEME)), ['http', 'https'], true)
    ? $footerSettings['facebook_url']
    : null;
$youtubeUrl = filter_var($footerSettings['youtube_url'], FILTER_VALIDATE_URL)
    && in_array(strtolower((string) parse_url($footerSettings['youtube_url'], PHP_URL_SCHEME)), ['http', 'https'], true)
    ? $footerSettings['youtube_url']
    : null;
$mapUrl = null;
if (filter_var($footerSettings['map_url'], FILTER_VALIDATE_URL)) {
    $mapScheme = strtolower((string) parse_url($footerSettings['map_url'], PHP_URL_SCHEME));
    $mapHost = strtolower((string) parse_url($footerSettings['map_url'], PHP_URL_HOST));
    if (in_array($mapScheme, ['http', 'https'], true)
        && in_array($mapHost, ['www.google.com', 'maps.google.com', 'google.com'], true)) {
        $mapUrl = $footerSettings['map_url'];
    }
}
?>
    <footer class="<?= !empty($isAdminDashboard) ? 'admin-site-footer' : '' ?>" style="padding: 20px 0 15px;">
        <!-- CSS tinh chỉnh chiều ngang footer rộng đều và cân đối hơn -->
        <style>
            .footer-centered-container {
                width: 100%;
                max-width: 1360px; /* Tăng độ rộng tối đa để 2 khung dài ra thêm đều đặn */
                padding-right: 25px;
                padding-left: 25px;
                margin-right: auto;
                margin-left: auto;
            }
            .footer-card-box {
                background: rgba(0, 0, 0, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.18);
                border-radius: 10px;
                padding: 1.1rem 1.4rem;
                height: 100%;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
                transition: border-color 0.2s ease;
            }
            .footer-card-box:hover {
                border-color: rgba(255, 255, 255, 0.35);
            }
            .footer-justify-text {
                text-align: justify;
            }
            .footer-map-frame {
                width: 100%;
                height: 200px;
                border: 0;
                border-radius: 8px;
                display: block;
            }
            @media (max-width: 576px) {
                .footer-map-frame { height: 170px; }
            }
        </style>

        <div class="footer-centered-container">
            <div class="row g-3 justify-content-center">
                <!-- Ô 1: Giới thiệu, mạng xã hội và Giờ mở cửa -->
                <div class="col-lg-6">
                    <div class="footer-card-box">
                        <h5 class="text-warning mb-2" style="font-family: 'Playfair Display', serif; font-weight: 700; letter-spacing: 0.03em; font-size: 1.05rem;">
                            <?= $footerEscape($footerSettings['museum_name']) ?>
                        </h5>
                        <p class="small mb-2 footer-justify-text" style="font-size: 0.85rem;"><?= nl2br($footerEscape($footerSettings['description'])) ?></p>
                        
                        <!-- Icon mạng xã hội -->
                        <div class="d-flex gap-2 mb-2">
                            <?php if ($facebookUrl !== null): ?>
                            <a href="<?= $footerEscape($facebookUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm rounded-circle" style="width:30px;height:30px;padding:0;line-height:28px;text-align:center" aria-label="Facebook">
                                <i class="fab fa-facebook-f fa-xs"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($youtubeUrl !== null): ?>
                            <a href="<?= $footerEscape($youtubeUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm rounded-circle" style="width:30px;height:30px;padding:0;line-height:28px;text-align:center" aria-label="YouTube">
                                <i class="fab fa-youtube fa-xs"></i>
                            </a>
                            <?php endif; ?>
                        </div>

                        <!-- Giờ mở cửa -->
                        <div class="border-top pt-2" style="border-color: rgba(255,255,255,0.15) !important;">
                            <h6 class="text-warning mb-1" style="font-size: 0.9rem;">Giờ mở cửa</h6>
                            <ul class="list-unstyled small <?= !empty($isAdminDashboard) ? 'footer-muted' : 'text-secondary' ?> mb-0" style="font-size: 0.82rem;">
                                <li class="mb-1">
                                    <i class="far fa-clock me-1 text-warning"></i><strong><?= $footerEscape($footerSettings['opening_days']) ?></strong>
                                </li>
                                <li class="ps-3 text-nowrap">
                                    <?= $footerEscape($footerSettings['opening_hours']) ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Ô 2: Liên hệ -->
                <div class="col-lg-6">
                    <div class="footer-card-box">
                        <h6 class="text-warning mb-2" style="font-size: 1.05rem;">Liên hệ</h6>
                        <ul class="list-unstyled small <?= !empty($isAdminDashboard) ? 'footer-muted' : 'text-secondary' ?> mb-0" style="font-size: 0.85rem;">
                            <li class="mb-2 footer-justify-text"><i class="fas fa-map-marker-alt me-2 text-warning"></i><?= $footerEscape($footerSettings['address']) ?></li>
                            <li class="mb-2"><i class="fas fa-phone me-2 text-warning"></i><?= $footerEscape($footerSettings['phone']) ?></li>
                            <li class="mb-2"><i class="fas fa-envelope me-2 text-warning"></i><?= $footerEscape($footerSettings['email']) ?></li>
                            <li class="mb-2"><i class="fas fa-globe me-2 text-warning"></i><a href="<?= $footerEscape($footerSettings['website']) ?>" target="_blank" class="text-white text-decoration-none"><?= $footerEscape($footerSettings['website']) ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if ($mapUrl !== null): ?>
            <div class="row g-3 mt-2">
                <div class="col-12">
                    <div class="footer-card-box">
                        <h6 class="text-warning mb-2" style="font-size: 1.05rem;">Vị trí Google Maps</h6>
                        <iframe
                            class="footer-map-frame"
                            src="<?= $footerEscape($mapUrl) ?>"
                            title="Bản đồ vị trí Bảo tàng Văn hóa Dân tộc Khmer"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen>
                        </iframe>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Nút chỉnh sửa footer dành cho Admin -->
            <?php if (!empty($isAdminDashboard)): ?>
            <div class="row mt-2">
                <div class="col-12 text-end">
                    <a href="footer_settings.php" class="btn btn-gold btn-sm" style="font-size: 0.78rem; padding: 3px 8px;">
                        <i class="fas fa-pen-to-square me-1"></i>Chỉnh sửa footer
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top small" style="border-color: rgba(255,255,255,0.2) !important;">
                <span><?= $footerEscape($footerSettings['copyright']) ?></span>
                <?php if (empty($isAdminDashboard)): ?>
                <a href="admin/login.php" class="text-decoration-none opacity-75" style="font-size:0.78rem;" aria-label="Đăng nhập quản trị">
                    <i class="fas fa-user-shield me-1"></i>Admin
                </a>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>