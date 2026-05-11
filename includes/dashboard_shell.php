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
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid px-3">
      <span class="navbar-brand mb-0 h1 fs-5 text-truncate" style="max-width:55%"><?php echo $pageTitle; ?></span>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#portalNavCollapse" aria-controls="portalNavCollapse" aria-expanded="false" aria-label="Toggle menu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="portalNavCollapse">
        <div class="ms-lg-auto d-flex flex-column flex-lg-row align-items-lg-center gap-2 py-2 py-lg-0">
          <span class="text-white-50 small order-lg-first"><?php echo $displayName; ?> · <span class="text-uppercase"><?php echo $roleLabel; ?></span></span>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($shell_nav_items as $nav): ?>
              <?php
                $href = htmlspecialchars($nav[0], ENT_QUOTES, 'UTF-8');
                $label = htmlspecialchars($nav[1], ENT_QUOTES, 'UTF-8');
              ?>
              <a class="btn btn-sm btn-outline-light" href="<?php echo $href; ?>"><?php echo $label; ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </nav>
  <main class="container pb-5">
    <?php echo $shell_body_html; ?>
  </main>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
