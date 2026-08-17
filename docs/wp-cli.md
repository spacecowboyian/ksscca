# WP-CLI access via WP Engine SSH

This is the interim path to write access while the site has no valid TLS certificate. The REST API cannot accept writes until HTTPS is fixed (see issues #1, #2, #8), but WP-CLI over SSH has no such dependency — it talks to WordPress directly on the server.

## What's already set up

- `bin/wp` — wp-cli wrapper that pins Homebrew's PHP 8.5 and silences vendor deprecation noise. Use this instead of a bare `wp`; the `php` first on this machine's PATH is a broken php@5.6 install.
- `wp-cli.yml` — `@prod` and `@staging` aliases, with `db drop`, `db reset`, and `site empty` disabled outright.

Verify the wrapper works:

```bash
bin/wp --version
```

## What's still needed

### 1. The WP Engine install name

The gateway hostname is derived from it: `<install>.ssh.wpengine.net`. It is **not** `ksscca` — that hostname doesn't resolve:

```bash
dig +short ksscca.ssh.wpengine.net   # returns nothing
```

Find it in the WP Engine portal (it's the install's name in the site list), or in wp-admin under the **WP Engine** menu. Then replace `INSTALL_NAME` in `wp-cli.yml`.

### 2. An SSH key uploaded to WP Engine

WP Engine authenticates SSH by public key only — no passwords. This machine already has `~/.ssh/id_ed25519`.

Portal → **Profile** → **SSH keys** → add the contents of:

```bash
cat ~/.ssh/id_ed25519.pub
```

Keys can take a few minutes to propagate to the install.

## Verify the connection

```bash
ssh INSTALL_NAME@INSTALL_NAME.ssh.wpengine.net "wp option get siteurl"
```

Expected today: `http://www.ksscca.org` — which is itself the bug tracked in #1.

Then through the alias:

```bash
bin/wp @prod option get siteurl
bin/wp @prod core version
```

## Useful commands once connected

Inventory and diagnostics:

```bash
bin/wp @prod plugin list --fields=name,status,version,update
bin/wp @prod theme list
bin/wp @prod user list --fields=ID,user_login,user_email,roles
bin/wp @prod post list --post_type=page --fields=ID,post_title,post_modified
bin/wp @prod core check-update
```

The plugin and user lists are the two biggest gaps in the current inventory — the audit so far was built entirely from unauthenticated HTTP and can't see versions, pending updates, or who else has access.

Creating an application password without the wp-admin UI (which hides the feature over http):

```bash
bin/wp @prod user application-password create Ian@futurehat.com claude-code --porcelain
```

Store the result as `WP_APP_PASS` in `env.org`. Note this still doesn't make REST writes *safe* over plain http — it's for use after #8 lands, or against the `*.wpengine.com` hostname which does have a valid certificate.

The HTTPS cutover search-replace from #8:

```bash
bin/wp @prod search-replace 'http://www.ksscca.org' 'https://www.ksscca.org' --all-tables --dry-run
```

Always dry-run first. This touches hundreds of absolute result-file links across the autocross and rallycross results pages.

## Safety notes

- WP Engine takes daily backups, but take a manual checkpoint in the portal before any `search-replace` or bulk update.
- `@staging` only exists if a staging environment has been created in the portal. If it hasn't, that's worth doing before the theme replacement work.
- Prefer `--dry-run` on anything that writes to the database in bulk.
