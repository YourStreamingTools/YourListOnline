<?php
require_once '/var/www/lib/session_bootstrap.php';
$alreadyLoggedIn = isset($_SESSION['access_token']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>YourListOnline &mdash; To-do lists for streamers</title>
  <meta name="description" content="A streaming-friendly to-do list with a built-in OBS browser source overlay, Twitch login, and shared moderator access. Sign in with Twitch and put your list on stream.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="dashboard/assets/dashboard.css?v=<?php echo @filemtime(__DIR__ . '/dashboard/assets/dashboard.css') ?: time(); ?>">
  <link rel="icon" href="https://cdn.yourlist.online/img/logo.png" type="image/png" />
  <link rel="apple-touch-icon" href="https://cdn.yourlist.online/img/logo.png">
  <style>
    body { margin: 0; background: var(--bg-base); color: var(--text-primary); }
    .yl-hero {
      background: linear-gradient(135deg, #6441a5 0%, #392e5c 100%);
      color: #fff;
      padding: 4rem 1.5rem 5rem;
      text-align: center;
    }
    .yl-hero img { width: 96px; margin-bottom: 1rem; }
    .yl-hero h1 { font-size: 2.6rem; font-weight: 800; margin: 0.25rem 0 1rem; }
    .yl-hero p { font-size: 1.1rem; max-width: 640px; margin: 0 auto 2rem; opacity: 0.9; }
    .yl-twitch-btn {
      display: inline-flex; align-items: center; gap: 0.6rem;
      background: #9146ff; color: #fff;
      padding: 0.85rem 1.6rem;
      border-radius: 6px;
      font-weight: 700; font-size: 1.05rem;
      text-decoration: none;
      box-shadow: 0 4px 14px rgba(0,0,0,0.25);
      transition: transform 0.1s, background 0.15s;
    }
    .yl-twitch-btn:hover { background: #7a2eff; color: #fff; transform: translateY(-1px); }
    .yl-features {
      max-width: 1100px; margin: 0 auto; padding: 4rem 1.5rem;
      display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 1.5rem;
    }
    .yl-feature {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      box-shadow: var(--shadow-sm);
    }
    .yl-feature i { font-size: 1.6rem; color: var(--accent); margin-bottom: 0.6rem; }
    .yl-feature h3 { margin: 0 0 0.4rem; font-size: 1.1rem; }
    .yl-feature p { margin: 0; color: var(--text-secondary); font-size: 0.92rem; line-height: 1.5; }
    .yl-section-title { text-align: center; margin: 3rem 0 1rem; font-size: 1.4rem; font-weight: 700; }
    .yl-how {
      max-width: 760px; margin: 0 auto; padding: 0 1.5rem 4rem;
      color: var(--text-secondary);
    }
    .yl-how ol { padding-left: 1.5rem; line-height: 1.8; }
    .yl-foot {
      border-top: 1px solid var(--border);
      padding: 1.5rem;
      text-align: center;
      color: var(--text-muted);
      font-size: 0.85rem;
    }
  </style>
</head>
<body>
  <header class="yl-hero">
    <img src="https://cdn.yourlist.online/img/logo.png" alt="YourListOnline">
    <h1>YourListOnline</h1>
    <p>A streaming-friendly to-do list. Add tasks, mark them done, and put a live overlay on stream &mdash; while your moderators help you stay on track.</p>
    <?php if ($alreadyLoggedIn): ?>
      <a href="dashboard/dashboard.php" class="yl-twitch-btn">
        <i class="fas fa-list-check"></i> Open your dashboard
      </a>
    <?php else: ?>
      <a href="dashboard/login.php" class="yl-twitch-btn">
        <i class="fab fa-twitch"></i> Login with Twitch
      </a>
    <?php endif; ?>
  </header>

  <section class="yl-features">
    <div class="yl-feature">
      <i class="fas fa-list-check"></i>
      <h3>Lists for streamers</h3>
      <p>Add and organise tasks by category. Mark them as you go and keep your stream focused.</p>
    </div>
    <div class="yl-feature">
      <i class="fas fa-eye"></i>
      <h3>Live OBS overlay</h3>
      <p>Drop a URL into a browser source &mdash; viewers see your list update in real time.</p>
    </div>
    <div class="yl-feature">
      <i class="fas fa-eye-slash"></i>
      <h3>Private tasks</h3>
      <p>Mark a task private and it won't appear on the overlay even when you're showing the rest.</p>
    </div>
    <div class="yl-feature">
      <i class="fas fa-user-shield"></i>
      <h3>Trusted mods</h3>
      <p>Grant Twitch moderators access to act as your channel. They can manage your list when you're busy on stream.</p>
    </div>
    <div class="yl-feature">
      <i class="fab fa-twitch"></i>
      <h3>One Twitch login</h3>
      <p>Same account as BotOfTheSpecter. Sign in once with Twitch and your list is right there.</p>
    </div>
    <div class="yl-feature">
      <i class="fas fa-palette"></i>
      <h3>Style your overlay</h3>
      <p>Custom font, colours (named or hex), shadow, bold, bullet vs numbered &mdash; tune it to your scene.</p>
    </div>
  </section>

  <h2 class="yl-section-title">How it works</h2>
  <div class="yl-how">
    <ol>
      <li><strong>Sign in with Twitch.</strong> The button above takes you to StreamersConnect, the same login used by BotOfTheSpecter.</li>
      <li><strong>Add a few tasks.</strong> Categorise them and mark anything you don't want viewers seeing as <em>private</em>.</li>
      <li><strong>Style your overlay.</strong> Open <em>OBS Viewing Options</em> and pick a font, colour, and list type.</li>
      <li><strong>Drop the overlay link into OBS.</strong> Add a Browser Source pointing at <code>https://overlay.botofthespecter.com/todolist.php?code=YOUR_API_KEY</code>.</li>
      <li><strong>Optional &mdash; invite your mods.</strong> On the Mods page, grant trusted moderators access so they can act as your channel and update the list during stream.</li>
    </ol>
  </div>

  <footer class="yl-foot">
    &copy; <?php echo date('Y'); ?> YourListOnline &middot;
    <a href="dashboard/login.php">Login</a> &middot;
    <a href="https://botofthespecter.com">BotOfTheSpecter</a>
  </footer>
</body>
</html>
