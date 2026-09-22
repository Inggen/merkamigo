# Run doc — Merkamigo (Laravel 12 + Livewire/Flux + Vite)

This checkout IS the main checkout (no worktree copying needed). It is normally served by Herd at https://merkamigo.test (nginx on 127.0.0.1:80). For the preview we run an independent `php artisan serve` instance.

## Reproduce artifacts (fresh checkout)

1. PHP 8.4 + MySQL running (Herd provides both). DB: MySQL database `merkamigo` (see `.env` — never commit its values; copy `.env` from the main checkout if starting fresh and adjust `APP_URL`).
2. Install PHP deps: `composer install`
3. Install JS deps: `npm install`
4. Build frontend assets: `npm run build` (produces `public/build/manifest.json`; required because the app loads built assets, no Vite dev server needed)
5. App key / storage: `php artisan key:generate` (if missing), `php artisan storage:link` (if missing)
6. Sessions/cache live in the MySQL database — make sure MySQL is up before serving.

## Run the server (preview)

Port: 8000 (Laravel default; Herd owns port 80, so use 8000).

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Detached (macOS): prefer launchd — plain `nohup … &` gets reaped when run from the Codebuff command runner:

```bash
launchctl submit -l buff.merkamigo.preview -- /bin/sh -c "cd /Users/johnalexanderramirezrodriguez/Herd/merkamigo && exec '/Users/johnalexanderramirezrodriguez/Library/Application Support/Herd/bin/php' artisan serve --host=127.0.0.1 --port=8000 > .freebuff/preview.log 2>&1"
# pid: launchctl print gui/$(id -u)/buff.merkamigo.preview | grep pid
# stop when done: launchctl remove buff.merkamigo.preview
```

Verify: `curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8000/` → expect 200/302.
