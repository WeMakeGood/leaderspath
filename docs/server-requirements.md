# Server Requirements

**Last Updated:** 2026-02-17

This document covers server configuration requirements for LeadersPath, with particular attention to SSE streaming for the Claude API chatbot.

## Minimum Requirements

| Component | Version | Notes |
|-----------|---------|-------|
| PHP | 8.2+ (targeting 8.3) | `strict_types`, type hints |
| WordPress | 6.4+ | REST API, block editor |
| Nginx | 1.18+ | SSE streaming support |
| MySQL | 8.0+ / MariaDB 10.6+ | Standard WP requirements |

### Required PHP Extensions

- `curl` (streaming API requests)
- `json`
- `mbstring`
- `openssl`
- `zip` (skill upload processing)

## SSE Streaming Configuration

The chatbot uses Server-Sent Events (SSE) to stream responses from the Claude API. Skill-based activities (e.g., organizational dossiers with web research) can take 2-5 minutes as Claude performs multiple web searches, fetches pages, and processes data. The default timeouts on most servers will kill these long-running requests.

### PHP-FPM Pool Configuration

**File:** `/etc/php/8.x/fpm/pool.d/<pool>.conf`

```ini
; CRITICAL: Default is 0 (off) on fresh installs, but many hosting providers
; set this to 30 or 60. Must be high enough for skill-based streaming.
request_terminate_timeout = 300
```

`request_terminate_timeout` is a **hard kill** (SIGKILL) by the FPM master process. It cannot be overridden by `set_time_limit()`, `ignore_user_abort()`, or any PHP code. If set to 60, all streaming requests longer than 60 seconds will die regardless of any other configuration.

After changing, restart PHP-FPM:

```bash
sudo systemctl restart php8.4-fpm
```

### PHP Configuration

**File:** `/etc/php/8.x/fpm/php.ini` (or pool-level override)

```ini
; Must be >= request_terminate_timeout
max_execution_time = 360

; Output buffering should be left at default (4096).
; The plugin flushes all output buffers before streaming.
output_buffering = 4096
```

### Nginx Configuration

SSE streaming requires specific Nginx directives to prevent buffering and premature connection termination. Add a dedicated location block for the streaming endpoint **before** the general PHP handler:

```nginx
server {
    # ... existing server config ...

    # SSE streaming for LeadersPath chatbot
    location ^~ /wp-json/leaderspath/v1/chat/stream {
        try_files $uri $uri/ /index.php?$query_string;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.x-fpm-<pool>.sock;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root/index.php;

            # Disable buffering — pass bytes through immediately.
            # Without this, Nginx buffers FastCGI responses and may trigger
            # ERR_HTTP2_PROTOCOL_ERROR when the buffer fills or the
            # connection goes idle.
            fastcgi_buffering off;

            # Match PHP-FPM's request_terminate_timeout.
            fastcgi_read_timeout 300;

            # Don't buffer the request body.
            fastcgi_request_buffering off;

            # Disable gzip — it breaks SSE chunking.
            gzip off;
        }
    }

    # ... general PHP handler (from hosting provider) ...
}
```

**Why `^~` prefix:** This tells Nginx to use this location block instead of regex matches defined elsewhere (e.g., a general `location ~ \.php$` block from the hosting provider's config). Without it, the general PHP handler would match and apply default buffering/timeout settings.

### Laravel Forge

On Forge-managed servers:

1. **Nginx:** Edit via Sites > (site) > Nginx Configuration. Add the SSE location block inside the `server {}` block, before `include forge-conf/.../site.conf;`
2. **PHP-FPM pool:** SSH in and edit `/etc/php/8.x/fpm/pool.d/<user>.conf`. Change `request_terminate_timeout = 300`. Restart FPM via Forge or `sudo systemctl restart php8.x-fpm`.
3. **php.ini:** Editable via Forge's PHP configuration UI or directly at `/etc/php/8.x/fpm/php.ini`.

## What the Plugin Does Automatically

The plugin handles several streaming concerns in code, but these **require** the server configuration above to function:

| Concern | Plugin solution | Server requirement |
|---------|-----------------|-------------------|
| PHP execution time | `set_time_limit(300)` | `request_terminate_timeout >= 300` (FPM must allow it) |
| User abort | `ignore_user_abort(true)` | N/A |
| Output buffering | Flushes all `ob_*` buffers before streaming | N/A |
| Nginx buffering | Sends `X-Accel-Buffering: no` header | `fastcgi_buffering off` (stronger, can't be overridden) |
| Idle connection | SSE keep-alive comments every 15s via curl progress callback | `fastcgi_read_timeout >= 300` |
| Connection type | Sends `Connection: keep-alive` header | N/A |

## Troubleshooting

### `ERR_HTTP2_PROTOCOL_ERROR` in browser console

**Cause:** Nginx is buffering the FastCGI response. When the buffer fills or the connection goes idle, HTTP/2 resets the stream.

**Fix:** Add `fastcgi_buffering off` to the SSE location block.

### `recv() failed (104: Connection reset by peer)` in Nginx error log

**Cause:** PHP-FPM killed the worker process (`request_terminate_timeout`).

**Fix:** Increase `request_terminate_timeout` in the FPM pool config.

### `upstream timed out (110: Connection timed out)` in Nginx error log

**Cause:** `fastcgi_read_timeout` is too low (default 60s).

**Fix:** Add `fastcgi_read_timeout 300` to the SSE location block.

### Stream works for simple chats but dies on skill-based activities

**Cause:** Simple chats complete in seconds. Skill-based activities with web research can take 2-5 minutes. The default 60-second timeouts are sufficient for simple chats but kill longer requests.

**Fix:** Apply all three configuration changes (FPM, Nginx timeout, Nginx buffering).

### Stream works on local dev but not on production

**Cause:** Local development environments (Local by Flywheel, MAMP, etc.) typically don't have aggressive timeouts. Production servers do.

**Fix:** Check all three timeout/buffering settings on the production server.
