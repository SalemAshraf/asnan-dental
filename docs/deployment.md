# Deployment notes

Nonsecret operational guidance for deploying Taqwim. The live shared host is
managed by a separate operations owner; this document records what a deploy
must preserve, not how to change host settings.

## Shared host (cPanel) PHP handler

The live host serves PHP 8.3 through a cPanel MultiPHP handler block written at
the top of the domain docroot `public/.htaccess`:

```
# php -- BEGIN cPanel-generated handler, do not edit
<IfModule mime_module>
  AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit
```

This block is host configuration, not application code. This repository ships
the stock Laravel `public/.htaccess`, so a deploy that overwrites the docroot
`.htaccess` removes the handler block and the site falls back to an older PHP.
Every deploy must either leave the live `.htaccess` untouched or restore the
cPanel block above the Laravel rewrite rules after copying files.

## Live host configuration choices (nonsecret)

Preserve these on the host when updating code:

- `APP_NAME=Taqwim`
- `APP_DEBUG=false`
- `QUEUE_CONNECTION=sync` — no queue worker runs on the host
- `MAIL_MAILER=log` — until a real mailer is configured
- Secure session settings as configured on the host

Runtime secrets (DB credentials, `APP_KEY`, OAuth secrets) stay host-owned and
never enter this repository or release artifacts.

## Demo fixture

`php artisan taqwim:demo` creates the fictional demo clinic once and refuses
to run if its demo records already exist; it never resets data. The demo
password comes from `--password-file` (0600, min 12 chars), the `DEMO_PASSWORD`
environment variable, or a hidden interactive prompt.
