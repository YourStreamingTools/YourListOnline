# Dashboard

Authenticated to-do list. Sign in flow lives in `login.php`; everything else
goes through `partials/auth.php`, which:

1. Bootstraps the session via `/var/www/lib/session_bootstrap.php`.
2. Loads the active user from `website.users` (with act-as awareness) via `partials/userdata.php`.
3. Builds `$modChannels` (channels the actor can act as) via `partials/mod_access.php`.
4. Opens the per-user MySQL connection as `$db` via `partials/user_db.php`.
5. Reads timezone from the per-user `profile` table.

After that, every page has access to:

- `$conn` &mdash; mysqli to the shared `website` DB (users, moderator_access, restricted_users)
- `$db` &mdash; mysqli to the active streamer's per-user DB (todos, categories, showobs, profile)
- `$user_id`, `$username`, `$twitchUserId`, `$twitchDisplayName`, `$twitch_profile_image_url`, `$email`, `$api_key`
- `$is_admin`, `$betaAccess`, `$authToken`, `$broadcasterID`, `$timezone`, `$greeting`
- `$modChannels`

## Schema (per-user database)

```sql
CREATE TABLE categories (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(255) NOT NULL
);

CREATE TABLE todos (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    objective TEXT NOT NULL,
    category INT(11) NOT NULL DEFAULT 1,
    private TINYINT(1) NOT NULL DEFAULT 0,
    completed TINYTEXT NOT NULL DEFAULT 'No',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE showobs (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    font VARCHAR(64),
    color VARCHAR(32),
    list VARCHAR(16) DEFAULT 'Bullet',
    font_size INT(11) DEFAULT 12,
    shadow TINYINT(1) DEFAULT 0,
    bold TINYINT(1) DEFAULT 0,
    show_completed TINYINT(1) DEFAULT 0
);

CREATE TABLE profile (
    timezone VARCHAR(64) DEFAULT 'UTC'
);

INSERT INTO categories (id, category) VALUES (1, 'Default');
```

Note that `todos` and `categories` here intentionally have **no `user_id`** column &mdash;
the per-user database itself is the tenancy boundary, the same convention BotOfTheSpecter uses.

## Schema (shared `website` database)

YourListOnline relies on tables already created by BotOfTheSpecter:

- `users` &mdash; account records keyed on `twitch_user_id`
- `moderator_access` &mdash; `(moderator_id, broadcaster_id)` pairs of Twitch user IDs
- `restricted_users` &mdash; banned `twitch_user_id` / `username` entries

If you are running this app standalone (without BotOfTheSpecter) you will need to create
those tables yourself; see the BotOfTheSpecter repository for their definitions.
