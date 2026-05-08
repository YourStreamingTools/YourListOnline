<?php
// Shared HTML head + top navigation. Expects $pageTitle, $activePage, $is_admin,
// $username, $twitchDisplayName, $twitch_profile_image_url, $modChannels,
// and the act-as session keys.
$pageTitle  = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? '';

$isActingAs = isset($_SESSION['admin_act_as_active']) && $_SESSION['admin_act_as_active'] === true;
$actAsTargetName = $_SESSION['admin_act_as_target_display_name'] ?? ($_SESSION['admin_act_as_target_username'] ?? '');
$actAsActorName = $_SESSION['admin_act_as_actor_username'] ?? '';
$canMod = !empty($modChannels);

$navItem = function (string $href, string $key, string $label) use ($activePage): string {
    $active = ($activePage === $key) ? ' class="is-active"' : '';
    return '<a href="' . htmlspecialchars($href) . '"' . $active . '>' . htmlspecialchars($label) . '</a>';
};

$displayNameForChrome = $twitchDisplayName ?? ($username ?? 'Streamer');
$profileAvatar = $twitch_profile_image_url ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>YourListOnline - <?php echo htmlspecialchars($pageTitle); ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/dashboard.css?v=<?php echo @filemtime(__DIR__ . '/../assets/dashboard.css') ?: time(); ?>">
  <link rel="icon" href="https://cdn.yourlist.online/img/logo.png" type="image/png" />
  <link rel="apple-touch-icon" href="https://cdn.yourlist.online/img/logo.png">
</head>
<body class="sp-body">
<?php if ($isActingAs && $actAsTargetName !== ''): ?>
<div class="sp-actas-banner">
  <span><i class="fas fa-user-secret"></i>
    Acting as <strong><?php echo htmlspecialchars($actAsTargetName); ?></strong>
    <?php if ($actAsActorName !== ''): ?>
      &middot; signed in as <?php echo htmlspecialchars($actAsActorName); ?>
    <?php endif; ?>
  </span>
  <a href="stop_act_as.php" class="sp-btn sp-btn-sm sp-btn-secondary"><i class="fas fa-sign-out-alt"></i> Stop acting as</a>
</div>
<?php endif; ?>
<nav class="sp-topnav">
  <div style="display:flex; align-items:center; flex-wrap:wrap; gap:0.5rem;">
    <a href="dashboard.php" class="sp-topnav-brand" style="text-decoration:none;">YourListOnline</a>
    <ul class="sp-topnav-menu">
      <li><?php echo $navItem('dashboard.php', 'dashboard', 'Dashboard'); ?></li>
      <li><?php echo $navItem('insert.php', 'insert', 'Add'); ?></li>
      <li><?php echo $navItem('remove.php', 'remove', 'Remove'); ?></li>
      <li>
        <button class="sp-topnav-link" type="button">Update <i class="fas fa-caret-down" style="font-size:0.7rem;"></i></button>
        <ul class="sp-topnav-submenu">
          <li><?php echo $navItem('update_objective.php', 'update_objective', 'Update Objective'); ?></li>
          <li><?php echo $navItem('update_category.php', 'update_category', 'Update Category'); ?></li>
        </ul>
      </li>
      <li><?php echo $navItem('completed.php', 'completed', 'Completed'); ?></li>
      <li>
        <button class="sp-topnav-link" type="button">Categories <i class="fas fa-caret-down" style="font-size:0.7rem;"></i></button>
        <ul class="sp-topnav-submenu">
          <li><?php echo $navItem('categories.php', 'categories', 'View Categories'); ?></li>
          <li><?php echo $navItem('add_category.php', 'add_category', 'Add Category'); ?></li>
        </ul>
      </li>
      <li><?php echo $navItem('mods.php', 'mods', 'Mods'); ?></li>
      <?php if ($canMod): ?>
        <li><?php echo $navItem('mod_channels.php', 'mod_channels', 'Mod Channels'); ?></li>
      <?php endif; ?>
    </ul>
  </div>
  <div class="sp-topnav-right">
    <ul class="sp-topnav-menu">
      <li>
        <button class="sp-topnav-link" type="button" style="display:flex; align-items:center; gap:0.5rem;">
          <?php if ($profileAvatar !== ''): ?>
            <img src="<?php echo htmlspecialchars($profileAvatar); ?>" alt="" style="width:24px; height:24px; border-radius:50%; object-fit:cover;">
          <?php endif; ?>
          <span><?php echo htmlspecialchars($displayNameForChrome); ?></span>
          <i class="fas fa-caret-down" style="font-size:0.7rem;"></i>
        </button>
        <ul class="sp-topnav-submenu" style="right:0; left:auto;">
          <li><?php echo $navItem('profile.php', 'profile', 'View Profile'); ?></li>
          <li><?php echo $navItem('obs_options.php', 'obs_options', 'OBS Viewing Options'); ?></li>
          <?php if ($isActingAs): ?>
            <li><a href="stop_act_as.php"><i class="fas fa-sign-out-alt"></i> Stop acting as</a></li>
          <?php endif; ?>
          <li><a href="logout.php">Logout</a></li>
        </ul>
      </li>
    </ul>
  </div>
</nav>
<main class="sp-shell">
  <h1 class="sp-page-title"><?php echo htmlspecialchars($greeting . ', ' . $displayNameForChrome . '!'); ?></h1>
