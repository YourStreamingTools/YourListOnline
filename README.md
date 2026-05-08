# YourListOnline

YourListOnline is a streaming-focused to-do list deployed at **yourlistonline.com.au**.
It is a second front end on top of the same accounts and per-user databases that
power the [BotOfTheSpecter](https://github.com/YourStreamingTools/BotOfTheSpecter)
dashboard. Sign in once with Twitch and your list is right there alongside the rest
of the bot's tooling.

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/T6T8D1Y2O)

## Features

- Twitch login via StreamersConnect (the same flow used by BotOfTheSpecter)
- Card-based dashboard with search, category filters, and live "X ago" timestamps
- Per-task **Private** flag that hides a task from the OBS overlay
- OBS browser-source overlay served from the BotOfTheSpecter overlay host (`overlay.botofthespecter.com/todolist.php?code=API_KEY`)
- Custom-hex colour support, font picker, font-size, bold, shadow, bullet/numbered list, optional show-completed
- **Moderator access**: streamers can grant Twitch mods permission to act as their channel and manage the list during stream
- **Mod Channels** view that lets a moderator pick whose list to manage
- Admins (`is_admin`) can act as any registered channel
- Dark-mode aware UI

## Architecture

YourListOnline shares infrastructure with BotOfTheSpecter on the same VPS:

- **Session bootstrap** &mdash; `/var/www/lib/session_bootstrap.php` (handles cookies + DB-backed sessions)
- **Primary DB connection** &mdash; `/var/www/config/db_connect.php` (the `website` DB: users, mods, etc.)
- **Per-user DB credentials** &mdash; `/var/www/config/database.php`
- **Twitch OAuth config** &mdash; `/var/www/config/twitch.php`
- **StreamersConnect API key** &mdash; `/var/www/config/main.php` (`streamersconnect_api_key`)
- **Users table** &mdash; `website.users` (shared with BotOfTheSpecter)
- **Moderator access** &mdash; `website.moderator_access` (shared)
- **Restricted accounts** &mdash; `website.restricted_users` (shared)
- **Todos / categories / overlay settings** &mdash; per-user database named after the streamer's Twitch login (e.g. `gfaundead.todos`, `gfaundead.categories`, `gfaundead.showobs`)
- **OBS overlay** &mdash; `https://overlay.botofthespecter.com/todolist.php?code=API_KEY`

### Project layout

```text
.
├── index.php                  Landing page with the Twitch login button
└── dashboard/                 Authenticated to-do dashboard
    ├── partials/
    │   ├── auth.php           Bootstraps session + loads userdata + opens $db
    │   ├── userdata.php       Loads the active user (handles "act as")
    │   ├── mod_access.php     Loads channels the actor can act as
    │   ├── user_db.php        Opens the per-user mysqli ($db)
    │   ├── header.php         Top nav, profile chrome, act-as banner
    │   └── footer.php         jQuery + SweetAlert + per-page scripts
    ├── assets/
    │   └── dashboard.css      The sp-* design system + dark mode
    ├── login.php              StreamersConnect Twitch OAuth flow
    ├── logout.php             Clears session, returns to /index.php
    ├── dashboard.php          Task list (search + filter + live timestamps)
    ├── insert.php             Add task (private flag included)
    ├── update_objective.php   Bulk edit objectives + categories + private
    ├── update_category.php    Bulk reassign categories
    ├── completed.php          Mark tasks completed
    ├── remove.php             Delete tasks
    ├── categories.php         List categories (Default cannot be removed)
    ├── add_category.php       Add a category
    ├── obs_options.php        Configure the BotOfTheSpecter overlay's look
    ├── profile.php            Twitch profile + reveal/copy API key
    ├── mods.php               Streamer view: grant mods access to act as them
    ├── mod_channels.php       Moderator view: pick a channel to act as
    ├── switch_channel.php     Enter act-as mode (with permission check)
    └── stop_act_as.php        Restore the original session
```

### Authentication flow

1. User hits `dashboard/login.php` (or any protected page &mdash; auth.php redirects).
2. `login.php` redirects to `https://streamersconnect.com/?service=twitch&login=<host>&return_url=...&scopes=...`.
3. StreamersConnect handles the Twitch OAuth handshake and redirects back with `auth_data` / `auth_data_sig` / `server_token`.
4. `login.php` verifies the payload (using the StreamersConnect API key when a signed/server token is present) and creates or updates the row in `website.users`, keyed on `twitch_user_id`.
5. The session now has `access_token`, `twitchUserId`, `username`, `api_key`, etc. &mdash; identical to the BotOfTheSpecter session shape.
6. On every protected page, `partials/auth.php` re-establishes the session via `/var/www/lib/session_bootstrap.php`, calls `userdata.php` to (re)load the user (handling act-as), `mod_access.php` to populate `$modChannels`, and `user_db.php` to open the per-user `$db` for todo data.

### Mods + "act as" model

- A streamer opens **Mods** and grants `moderator_access` rows to their Twitch moderators.
- That moderator now sees the streamer in **Mod Channels** and can hit **Act as this channel**.
- `switch_channel.php` verifies authority (admin, the bot user, an entry in `moderator_access`, or a successful `helix/moderation/channels` check) before swapping `$_SESSION['username']` to the target's login. The original session is preserved in `$_SESSION['admin_act_as_original']`.
- `partials/user_db.php` keys the per-user DB on `$_SESSION['username']`, so all subsequent queries hit the streamer's database.
- A persistent banner appears across the dashboard while act-as is active. **Stop acting as** restores the original session.

## Deployment notes

- This site assumes a same-VPS deployment as BotOfTheSpecter so the `require '/var/www/...'`
  paths resolve. If you ever split it onto its own host, copy or sync those config files
  and `session_bootstrap.php`, and update the StreamersConnect application origin to allow
  `yourlistonline.com.au`.
- Cookies are scoped by whatever domain `session_bootstrap.php` decides at runtime. If that
  bootstrap currently hard-codes `.botofthespecter.com`, make it host-aware (or branch on
  `$_SERVER['HTTP_HOST']`) so the YourListOnline session cookie is set on `.yourlistonline.com.au`.
- The StreamersConnect application must allow `yourlistonline.com.au` as a return origin.
- The OBS overlay is the BotOfTheSpecter one; nothing else is served from this repo for the
  browser source.

## Local development

This repo is a plain PHP project. To run it locally you'll need:

- Apache or nginx + PHP 8.x
- Read access to a copy of `/var/www/config/` and `/var/www/lib/` from the BotOfTheSpecter VPS
- The `website` MySQL database (or a clone with the same schema) and a per-user database for your test account
