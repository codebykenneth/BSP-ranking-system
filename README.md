# BSP Ranking System

A practical, self-contained scout ranking system built with plain PHP, MySQL, HTML/CSS/JS — designed to run on XAMPP with no frameworks.

> **Disclaimer:** This system is **not officially affiliated with the Boy Scouts of the Philippines**. It is built for educational or school/troop administrative use only.

---

## 1. Folder Structure


```
/bsp-ranking-system
  /config
    database.php          -> PDO connection settings
  /includes
    auth.php               -> session bootstrap + login guard + base_url()
    functions.php          -> helpers, RANK_LEVELS map, scoring formula
    header.php              -> shared <head> + opening markup
    sidebar.php             -> shared nav
    footer.php               -> shared closing markup + JS include
  /pages
    dashboard.php
    scouts.php               -> scout list, search/filter, delete
    scout_form.php            -> add + edit scout (one form)
    activities.php            -> activity list, filter, delete
    activity_form.php         -> add + edit activity (one form)
    leaderboard.php            -> top 10 + podium
    reports.php                 -> full roster + print/export view
  /assets
    /css/style.css
    /js/script.js
    /uploads                    -> uploaded scout profile photos
  index.php                -> login page
  login_process.php        -> handles login POST
  logout.php               -> destroys session
  generate_hash.php        -> utility to create new bcrypt password hashes
  database.sql             -> schema + sample data
  README.md
```

---

## 2. XAMPP Setup Instructions


**If this is a brand new install:**

1. **Copy the project folder**
   Copy the whole `bsp-ranking-system` folder into your XAMPP `htdocs` directory, e.g.
   `C:\xampp\htdocs\bsp-ranking-system` (Windows) or `/Applications/XAMPP/htdocs/bsp-ranking-system` (Mac).

2. **Start Apache and MySQL**
   Open the XAMPP Control Panel and start both **Apache** and **MySQL**.

3. **Create the database**
   - Go to `http://localhost/phpmyadmin`.
   - Click **Import**, choose the `database.sql` file from the project folder, and click **Go**.
   - This creates the `bsp_ranking_system` database with all tables (`users`, `scouts`, `activities`, `events`, `attendance`) and sample data.

**If you already have this project running with data in it** (don't re-import `database.sql` — that resets everything). Instead:

- Replace all the project files with the new ones.
- In phpMyAdmin, open the **SQL** tab on your `bsp_ranking_system` database and run `migration_add_scout_login.sql`, then `migration_add_attendance_system.sql`, then `migration_add_announcements.sql`. All three only *add* new columns/tables — your existing scouts, activities, and admin accounts are untouched.

4. **Check the DB connection settings**
   Open `config/database.php`. The defaults match a stock XAMPP install:
   ```php
   DB_HOST = 'localhost'
   DB_NAME = 'bsp_ranking_system'
   DB_USER = 'root'
   DB_PASS = ''
   ```
   Change these only if your MySQL user/password differ.

5. **Open the app**
   Visit `http://localhost/bsp-ranking-system/` in your browser.

6. **Log in**
   - Username: `admin`
   - Password: `admin123`

   To change this password later, visit `generate_hash.php?password=yourNewPassword`, copy the generated hash, and update the `password` column for the `admin` row in the `users` table via phpMyAdmin. **Delete `generate_hash.php` once you're done** — it shouldn't stay on a live server.

That's it — the dashboard, scout/activity/event management, leaderboard, and reports are all live for admins, and scouts can register their own portal accounts from the login page.

---

## 3. Two Separate Accounts Systems


This system now has two completely separate logins, so scouts can never edit data — only view their own:

| | Admin | Scout |
|---|---|---|
| Log in at | `index.php` (main login) | `scout_login.php` |
| Can do | Add/edit/delete scouts, activities, events; take attendance; post announcements; view all reports | View only: their own points, attendance %, score, troop rank, attendance history, and announcements. Can change their own profile picture. |
| Register at | `register.php` | `register_scout.php` |
| Session variable | `$_SESSION['admin_id']` | `$_SESSION['scout_id']` |
| Guard function | `require_login()` | `require_scout_login()` |

Every admin page (`pages/*.php`) calls `require_login()`, so a scout session can never reach them — visiting `pages/scouts.php` as a scout just redirects back to the admin login. The scout portal (`scout_portal.php`) and profile page (`scout_profile.php`) are separate, read-only-except-for-photo pages with their own minimal layout (no sidebar with edit links), so there's no accidental exposure of admin controls.

---

## 4. Attendance System


Attendance is no longer just a number an admin types in — it's built from real records:

- **Events** (`pages/events.php`, `pages/event_form.php`) — admins create meetings/events with a name and date.
- **Taking attendance** (`pages/attendance_checkin.php`) — for any event, the admin sees the full scout roster in one table and marks each scout Present / Late / Absent / Excused, then saves once. There's also a **Print Sheet** button for a paper roster if attendance needs to be taken offline first.
- **Auto-calculated attendance %** — `get_attendance_percentage()` in `includes/functions.php` computes each scout's attendance from their real records: Present = full credit, Late = half credit, Absent = none, Excused doesn't count against them. This is what feeds the ranking formula now — not a manually typed number.
- **Fallback** — a scout with zero attendance records yet (brand new, or before this system existed) falls back to the "Starting Attendance %" field on their profile, so nothing breaks for existing data.
- **Dashboard alert** — any scout below 75% attendance shows up in a "Needs Attention" panel on the admin dashboard.
- **Scout portal** — each scout can log in and see their own attendance history (which meetings they were marked at, and their status) alongside their points, score, and troop rank.

---

## 5. Announcements


Admins can post updates for scouts to see:

- **`pages/announcements.php`** — admin list + delete
- **`pages/announcement_form.php`** — admin post a new one (title + message)
- Scouts see the 5 most recent announcements right on their portal (`scout_portal.php`), newest first

## 6. Scout Profile Picture


Scouts can update their own photo (and only their photo — name, troop, and rank still require an admin) at **`scout_profile.php`**, linked from their portal. The old photo file is automatically deleted from `assets/uploads/` when they upload a new one, so orphaned images don't pile up.

---

## 7. Ranking Logic


Each scout's **Total Score** is computed live (never stored) using:

```
Total Score = (Activity Points × 0.5) + (Rank Level × 0.3) + (Attendance × 0.2)
```

- **Activity Points** — the sum of `points` from every row in `activities` belonging to that scout.
- **Rank Level** — a numeric value (1–7) mapped from the scout's official BSP rank name:

  | Rank Name           | Level |
  |----------------------|:---:|
  | Scout                | 1 |
  | Tenderfoot            | 2 |
  | Second Class Scout    | 3 |
  | First Class Scout     | 4 |
  | Star Scout            | 5 |
  | Senior Scout          | 6 |
  | Eagle Scout           | 7 |

- **Attendance** — a percentage from 0–100, stored directly on the scout's record.

The formula and rank map both live in `includes/functions.php`, in `calculate_score()` and the `RANK_LEVELS` constant — edit them there if you need a different weighting or rank progression. `get_ranked_scouts()` pulls every scout with their summed activity points, computes the score, and sorts descending — this single function powers the dashboard's Top 5, the Leaderboard's Top 10, and the full Reports table, so the ranking logic only exists in one place.

---

## 8. Features Included


- **Auth:** session-based admin login, bcrypt password hashing, logout, route guard (`require_login()`) on every protected page.
- **Dashboard:** total scouts, total activities, troop count, average attendance, Top 5 leaderboard preview.
- **Scout CRUD:** add/edit/delete, search by name, filter by troop, optional profile photo upload (JPG/PNG/GIF/WEBP, 2MB limit).
- **Activity CRUD:** add/edit/delete, filter by scout, points + date tracking.
- **Leaderboard:** Top 10 with a highlighted podium (🥇🥈🥉) for the top 3.
- **Reports:** full ranked roster with a dedicated print-friendly view (`reports.php?print=1`) that auto-opens the browser print dialog — use "Save as PDF" in the print dialog to export.
- **Security:** all queries use PDO prepared statements; passwords are hashed with `password_hash()`/`password_verify()`; output is escaped with `htmlspecialchars()` via the `e()` helper.

## 9. Extending It Later


- Add more admin accounts by inserting rows into `users` (use `generate_hash.php` to get a hash).
- Add fields to `scouts` (e.g. contact info, badges earned) — extend the table, the form in `scout_form.php`, and the list in `scouts.php`.
- Multi-level roles (e.g. troop leader vs. super-admin) can be added with a `role` column on `users` and a check in `auth.php`.
- The scoring weights (`0.5 / 0.3 / 0.2`) and rank levels are centralized in `includes/functions.php` for easy tuning.
