#!/usr/bin/env bash
# Installed on the server as a forced SSH command; receives a Git archive on stdin.
set -Eeuo pipefail
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
app=/opt/lion-home-services
exec 9>/var/lock/lion-home-services-deploy.lock
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }
tmp=$(mktemp -d /opt/lion-deploy.XXXXXXXX)
activated=0
old_image=$(docker inspect --format '{{.Image}}' lion-home-services-web-1)
cleanup() {
  status=$?
  if (( status != 0 && activated )); then
    echo 'Deployment failed; restoring the previous application.' >&2
    for dir in app docker public; do rsync -a --delete "$tmp/backup/$dir/" "$app/$dir/"; done
    cp "$tmp/backup/Dockerfile" "$tmp/backup/compose.yaml" "$app/"
    docker tag "$old_image" lion-home-services-web
    (cd "$app" && docker compose -p lion-home-services up -d --no-build --force-recreate) || echo 'Rollback failed; inspect the server immediately.' >&2
  fi
  rm -rf "$tmp"
  exit "$status"
}
trap cleanup EXIT
mkdir "$tmp/new" "$tmp/backup"
tar -xf - -C "$tmp/new"
for item in Dockerfile compose.yaml app docker public; do test -e "$tmp/new/$item"; done
if [[ -f "$app/.env" ]]; then cp "$app/.env" "$tmp/new/.env"; fi
(cd "$tmp/new" && docker compose -p lion-home-services build web)
for dir in app docker public; do cp -a "$app/$dir" "$tmp/backup/"; done
cp "$app/Dockerfile" "$app/compose.yaml" "$tmp/backup/"
activated=1
for dir in app docker public; do rsync -a --delete "$tmp/new/$dir/" "$app/$dir/"; done
cp "$tmp/new/Dockerfile" "$tmp/new/compose.yaml" "$app/"
(cd "$app" && docker compose -p lion-home-services up -d --no-build --force-recreate)
for attempt in {1..30}; do
  if [[ "$(curl -fsS --max-time 3 http://127.0.0.1:8088/health.php || true)" == ok ]]; then
    activated=0
    echo 'Deployment is healthy.'
    exit 0
  fi
  sleep 2
done
echo 'Application health check failed.' >&2
exit 1
