# How to turn on the "All Concerns" console

This adds a supervisor-only console that lists every subscriber's concerns
(tickets + chats + survey feedback), with filters and an "include test data" toggle.

## What was added / changed

- NEW: `CSR/concerns/all_concerns.php`  — the console page
- NEW: `CSR/concerns/admin_guard.php`   — controls who is allowed in
- NEW: `CSR/concerns/HOW_TO_TURN_ON.md` — this file
- CHANGED: `CSR/dashboard/csr_dashboard.php` — adds an "All Concerns" button (shown to admins only)

## Step 1 — Set your supervisor login

Open `CSR/concerns/admin_guard.php` and put your supervisor's CSR username in the list:

    $CSR_ADMIN_USERS = ['admin'];          // <- change 'admin' to your supervisor's login
    // e.g. $CSR_ADMIN_USERS = ['jenny.cruz'];

Only the logins in this list will see the button and can open the console.

## Step 2 — Publish to your live site

Your site auto-deploys from GitHub (branch `main`) to Render. Get these files into GitHub:
the four files listed above. Once they land on `main`, Render redeploys automatically in a
few minutes. (If you're not sure how to upload to GitHub, ask and I'll walk you through it.)

## Step 3 — Rotate the database password first

Before trusting this in production, reset your Neon database password (it was exposed in the
public repo) and keep the code using the connection your site already uses.

## Step 4 — Open it

Log in to the CSR dashboard as your supervisor account, then click **All Concerns** in the top
bar (or the sidebar). Direct link: `/CSR/concerns/all_concerns.php`.

## If anything looks wrong

This page only *reads* data — it never changes anything. To remove it completely, delete the
`CSR/concerns/` folder and remove the two "ALL CONCERNS" button blocks from `csr_dashboard.php`.

## Note on testing

It hasn't been run against your live database yet (that can't be done from here). Please test it
on your site — ideally right after deploying — and tell me if any column or label looks off, so
I can adjust it to match your real data.
