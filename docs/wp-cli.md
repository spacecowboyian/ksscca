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

### ~~1. The WP Engine install name~~ — done

The install is **`kansasregion`**, confirmed from the WP Engine panel in wp-admin (`kansasregion.wpengine.com`, `kansasregion.sftp.wpengine.com`) and by DNS:

```bash
dig +short kansasregion.ssh.wpengine.net   # ssh.gcp-p-us-west1-farm-06.wpesvc.net → 34.168.124.108
```

Already filled into `wp-cli.yml`.

### 2. An SSH key uploaded to WP Engine

WP Engine authenticates SSH by public key only — no passwords. This machine already has `~/.ssh/id_ed25519`.

Portal → **Profile** → **SSH keys** → add the contents of:

```bash
cat ~/.ssh/id_ed25519.pub
```

Keys can take a few minutes to propagate to the install.

## Verify the connection

```bash
ssh kansasregion@kansasregion.ssh.wpengine.net "wp option get siteurl"
```

Until the key is uploaded this returns `Permission denied (publickey)`.

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

Store the result as `WP_APP_PASS` in `env.org`. Note this still doesn't make REST writes *safe* over plain http — it's for use after #8 lands.

The `*.wpengine.com` hostname is **not** a workaround, despite having a valid certificate: WordPress issues a canonical 301 from `https://kansasregion.wpengine.com/` straight back to `http://www.ksscca.org/`, so the request never stays on TLS.

The HTTPS cutover search-replace from #8:

```bash
bin/wp @prod search-replace 'http://www.ksscca.org' 'https://www.ksscca.org' --all-tables --dry-run
```

Always dry-run first. This touches hundreds of absolute result-file links across the autocross and rallycross results pages.

## Safety notes

- WP Engine takes daily backups, but take a manual checkpoint in the portal before any `search-replace` or bulk update.
- `@staging` only exists if a staging environment has been created in the portal. If it hasn't, that's worth doing before the theme replacement work.
- Prefer `--dry-run` on anything that writes to the database in bulk.
