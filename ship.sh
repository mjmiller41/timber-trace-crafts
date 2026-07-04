#!/bin/bash
#
# ship.sh — one-command, zero-touch production deploy for Timber Trace Crafts.
#
# Collapses the entire release into a single invocation:
#   1. Re-encrypt .env.production -> .env.production.encrypted IF it drifted
#   2. Build frontend assets and commit public/build/ (no Node on the server)
#   3. Commit the generated artifacts and push main to GitHub
#   4. Wait (poll) until Hostinger has published the pushed commit into public_html
#   5. Run deploy.sh on the server (decrypt env, migrate, cache) over SSH
#   6. Smoke-test the live site
#
# No manual SSH, no manual `env:encrypt`, no manual deploy.sh. The encryption
# key is read from ~/.secrets/ttc-env-key (mirror of the server copy, chmod 600,
# outside git). Passwordless SSH must be configured.
#
# Usage:
#   ./ship.sh                 Full pipeline (prep -> push -> remote deploy -> verify)
#   ./ship.sh --prep-only     Encrypt-if-needed + build + commit, no push/deploy
#   ./ship.sh --remote-only   Skip prep/push; wait for the current local main SHA
#                             to publish, then run deploy.sh + verify. Used by the
#                             pre-push hook so a bare `git push` still auto-deploys.
#   ./ship.sh --remote-only <SHA>   Same, but wait for an explicit SHA.
#
set -euo pipefail

# ----- config ---------------------------------------------------------------
SSH_PORT=65002
SSH_HOST="u903552178@5.183.10.138"
REMOTE_DIR="domains/timbertracecrafts.com/public_html"   # relative to remote $HOME
KEY_FILE="$HOME/.secrets/ttc-env-key"
SITE_URL="https://timbertracecrafts.com"
PUBLISH_TIMEOUT=480      # max seconds to wait for Hostinger to publish the push
POLL_INTERVAL=10

SSH="ssh -o BatchMode=yes -o ConnectTimeout=15 -p $SSH_PORT $SSH_HOST"
REPO_ROOT="$(git rev-parse --show-toplevel)"
cd "$REPO_ROOT"

# ----- helpers --------------------------------------------------------------
log()  { printf '\033[1;36m>>> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!!! %s\033[0m\n' "$*" >&2; }
die()  { printf '\033[1;31mXXX %s\033[0m\n' "$*" >&2; exit 1; }

remote() { $SSH "cd $REMOTE_DIR && $1"; }

server_head() { remote "git rev-parse HEAD" 2>/dev/null || true; }

# Returns 0 (drift) if .env.production differs from the committed ciphertext and
# therefore needs re-encrypting; returns 1 if in sync or if there is no local
# .env.production to encrypt.
env_has_drift() {
    [ -f .env.production ] || return 1
    [ -f .env.production.encrypted ] || return 0
    local tmp; tmp="$(mktemp "${TMPDIR:-/tmp}/ttc-env.XXXXXX")"
    # shellcheck disable=SC2064
    trap "rm -f '$tmp'" RETURN
    if ! php artisan env:decrypt --env=production --key="$KEY" --filename="$tmp" --force >/dev/null 2>&1; then
        return 0   # can't decrypt committed snapshot -> treat as drift, re-encrypt
    fi
    # Compare order- and CRLF-insensitively (memory: .env.production is CRLF).
    if diff -q \
        <(tr -d '\r' < .env.production | sed '/^$/d' | sort) \
        <(tr -d '\r' < "$tmp"          | sed '/^$/d' | sort) >/dev/null; then
        return 1   # in sync
    fi
    return 0       # drift
}

load_key() {
    [ -f "$KEY_FILE" ] || die "Missing $KEY_FILE — cannot encrypt/deploy. Mirror it from the server: ssh -p $SSH_PORT $SSH_HOST 'cat ~/.secrets/ttc-env-key' > $KEY_FILE && chmod 600 $KEY_FILE"
    KEY="$(cat "$KEY_FILE")"
    [ -n "$KEY" ] || die "$KEY_FILE is empty."
}

# ----- pipeline stages ------------------------------------------------------
prep() {
    load_key

    if env_has_drift; then
        log "Production env drifted — re-encrypting .env.production"
        php artisan env:encrypt --env=production --key="$KEY" --force >/dev/null
        git add .env.production.encrypted
        git commit -m "chore: re-encrypt production env" >/dev/null
        log "Committed refreshed .env.production.encrypted"
    else
        log "Production env in sync — no re-encryption needed"
    fi

    log "Building frontend assets"
    npm run build >/dev/null 2>&1 || die "npm run build failed"
    git add public/build/
    if git diff --cached --quiet public/build/; then
        log "Assets unchanged"
    else
        git commit -m "chore: rebuild frontend assets" >/dev/null
        log "Committed rebuilt assets"
    fi
}

push_main() {
    [ "$(git branch --show-current)" = "main" ] || die "Not on 'main' (on '$(git branch --show-current)'). Check out and merge to main first — ship deploys main."
    log "Pushing main to origin"
    TTC_SHIP=1 git push origin main   # TTC_SHIP=1 tells the pre-push hook ship owns the deploy
}

# Wait until the server's published HEAD matches $1, then run deploy.sh.
remote_deploy() {
    local target="$1"
    log "Waiting for Hostinger to publish ${target:0:7} (timeout ${PUBLISH_TIMEOUT}s)"
    local waited=0 head
    while :; do
        head="$(server_head)"
        [ "$head" = "$target" ] && break
        (( waited >= PUBLISH_TIMEOUT )) && die "Timed out waiting for publish. Server HEAD=${head:0:7}, expected ${target:0:7}. Run './ship.sh --remote-only $target' once it lands."
        sleep "$POLL_INTERVAL"; waited=$(( waited + POLL_INTERVAL ))
    done
    log "Published (${target:0:7}) — running deploy.sh on server"
    remote "bash deploy.sh"
}

verify() {
    log "Smoke-testing $SITE_URL"
    local code
    code="$(curl -fsS -o /dev/null -w '%{http_code}' --max-time 30 "$SITE_URL" || true)"
    if [ "$code" = "200" ]; then
        log "Live site returned 200 — deploy verified"
    else
        die "Smoke test failed: $SITE_URL returned '${code:-no response}'. Investigate before assuming success."
    fi
}

# ----- entrypoint -----------------------------------------------------------
MODE="${1:-full}"
case "$MODE" in
    --prep-only)
        prep
        log "Prep complete (no push/deploy)."
        ;;
    --remote-only)
        load_key
        target="${2:-$(git rev-parse main)}"
        remote_deploy "$target"
        verify
        ;;
    full)
        prep
        push_main
        remote_deploy "$(git rev-parse main)"
        verify
        log "=== Deploy complete ==="
        ;;
    *)
        die "Unknown option '$MODE'. Use: (none) | --prep-only | --remote-only [SHA]"
        ;;
esac
