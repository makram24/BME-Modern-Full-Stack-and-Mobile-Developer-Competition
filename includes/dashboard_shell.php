<?php
declare(strict_types=1);

/**
 * Shared layout for role dashboards. Caller sets $shell_title, $shell_nav_items, $shell_body_html.
 *
 * @var string $shell_title
 * @var array<int, array{0: string, 1: string}> $shell_nav_items pairs [href, label]
 * @var string $shell_body_html
 */
if (!isset($shell_title, $shell_nav_items, $shell_body_html)) {
    http_response_code(500);
    exit('Dashboard shell misconfigured.');
}

$displayName = htmlspecialchars((string) (current_username() ?? 'User'), ENT_QUOTES, 'UTF-8');
$roleLabel = htmlspecialchars(str_replace('_', ' ', (string) (current_role() ?? '')), ENT_QUOTES, 'UTF-8');
$pageTitle = htmlspecialchars($shell_title, ENT_QUOTES, 'UTF-8');

$portal_nav_script = static function (string $href): string {
    $path = parse_url($href, PHP_URL_PATH);
    if (is_string($path) && $path !== '') {
        return basename($path);
    }

    return basename(explode('?', $href, 2)[0]);
};

$currentScript = basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));

$role = (string) (current_role() ?? 'student');
$portalHomeMap = [
    'teacher' => 'teacher_dashboard.php',
    'administrator' => 'admin_dashboard.php',
    'super_administrator' => 'superadmin_dashboard.php',
];
$portalHome = $portalHomeMap[$role] ?? 'student_dashboard.php';
$portalHomeHref = htmlspecialchars($portalHome, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?> — Portal</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="portal-body">
  <a class="visually-hidden-focusable portal-skip-link btn btn-sm btn-primary position-absolute m-2" href="#portal-main">Skip to content</a>
  <header class="portal-header">
    <div class="portal-header-bar">
      <div class="portal-header-primary">
        <a class="portal-brand" href="<?php echo $portalHomeHref; ?>">
          <span class="portal-brand-mark" aria-hidden="true"></span>
          <span class="portal-brand-text">School portal</span>
        </a>
        <span class="portal-page-title text-truncate" title="<?php echo $pageTitle; ?>"><?php echo $pageTitle; ?></span>
        <button class="portal-menu-btn d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#portalNavCollapse" aria-controls="portalNavCollapse" aria-expanded="false" aria-label="Open menu">
          <i class="bi bi-list" aria-hidden="true"></i>
        </button>
      </div>
      <div class="portal-user-chip d-none d-sm-flex">
        <span class="portal-user-name"><?php echo $displayName; ?></span>
        <span class="portal-user-role"><?php echo $roleLabel; ?></span>
      </div>
    </div>
    <div class="collapse portal-nav-collapse" id="portalNavCollapse">
      <nav class="portal-nav" aria-label="Main navigation">
        <div class="portal-nav-inner">
          <?php foreach ($shell_nav_items as $nav): ?>
            <?php
              $href = htmlspecialchars($nav[0], ENT_QUOTES, 'UTF-8');
              $label = htmlspecialchars($nav[1], ENT_QUOTES, 'UTF-8');
              $isLogout = strpos($nav[0], 'logout.php') !== false;
              $isActive = !$isLogout && $portal_nav_script($nav[0]) === $currentScript;
              $linkClass = 'portal-nav-link' . ($isLogout ? ' portal-nav-link--muted' : '') . ($isActive ? ' is-active' : '');
            ?>
            <a class="<?php echo $linkClass; ?>" href="<?php echo $href; ?>"><?php echo $label; ?></a>
          <?php endforeach; ?>
        </div>
        <div class="portal-user-chip portal-user-chip--mobile d-sm-none">
          <span class="portal-user-name"><?php echo $displayName; ?></span>
          <span class="portal-user-role"><?php echo $roleLabel; ?></span>
        </div>
      </nav>
    </div>
  </header>
  <main id="portal-main" class="portal-main">
    <div class="portal-main-inner">
      <?php echo $shell_body_html; ?>
    </div>
  </main>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      var el = document.getElementById('portalNavCollapse');
      if (!el || !window.bootstrap) return;
      var collapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
      el.addEventListener('click', function (e) {
        var t = e.target;
        if (t.closest && t.closest('a.portal-nav-link') && window.innerWidth < 992) collapse.hide();
      });
    })();
  </script>
</body>
</html>
