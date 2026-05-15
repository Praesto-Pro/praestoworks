#!/usr/bin/env bash
set -Eeuo pipefail

readonly REPO_URL="https://github.com/Praesto-Pro/praestoworks.git"
readonly BRANCH="master"
readonly RELEASE_ROOT="/srv/praestoworks-qa"
readonly APP_DIR="${RELEASE_ROOT}/current"
readonly LOCK_FILE="/var/lock/praestoworks-qa-deploy.lock"
readonly TARGET_REF="${1:-origin/${BRANCH}}"

if [[ "${EUID}" -ne 0 ]]; then
  echo "deploy-qa.sh must be run as root, usually via sudo -n." >&2
  exit 1
fi

command -v git >/dev/null 2>&1 || {
  echo "git is required on the QA server." >&2
  exit 1
}

mkdir -p "${RELEASE_ROOT}"
touch "${LOCK_FILE}"

exec 9>"${LOCK_FILE}"
flock -n 9 || {
  echo "Another QA deploy is already running." >&2
  exit 1
}

if [[ ! -d "${APP_DIR}/.git" ]]; then
  rm -rf "${APP_DIR}"
  git clone --branch "${BRANCH}" "${REPO_URL}" "${APP_DIR}"
fi

git_app() {
  git -c "safe.directory=${APP_DIR}" -C "${APP_DIR}" "$@"
}

git_app remote set-url origin "${REPO_URL}"
git_app fetch --prune origin "${BRANCH}"
git_app checkout -B "${BRANCH}" "${TARGET_REF}"
git_app reset --hard "${TARGET_REF}"

if id www-data >/dev/null 2>&1; then
  chown -R www-data:www-data "${APP_DIR}"
fi

echo "QA deploy complete: $(git_app rev-parse --short HEAD)"
