set -e
# Deployment server info
DEPLOY_USER="IT490"
DEPLOY_HOST="192.168.1.63"   #  DB IP
DEPLOY_BASE_DIR="/home/IT490/deploy/Bundles"

# Root
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Argument check
if [ $# -ne 1 ]; then
  echo "Usage: $0 <bundle-name>"
  echo "Valid bundles: web backend dmz"
  exit 1
fi
BUNDLE="$1"

#  bundle contents
case "$BUNDLE" in
  web)
    REMOTE_SUBDIR="web"
    FILES="
web/index.php
web/login.php
web/logout.php
web/landing.php
web/register.php
web/registration.php
web/browse.php
web/details.php
web/search.php
web/reviews.php
web/my_reviews.php
web/anticipated.php
web/discussion.php
web/watchlist.php
web/main.css
web/testRabbitMQ.ini
web/movieRabbitMQ.ini
shared/path.inc
shared/rabbitMQLib.inc
shared/get_host_info.inc
shared/host.ini
"
    ;;
  backend)
    REMOTE_SUBDIR="backend"
    FILES="
backend/authServer.php
backend/logServer.php
backend/connection.php
backend/reviewServer.php
backend/discussionServer.php
backend/watchlistServer.php
backend/dbRabbitMQ.ini
shared/path.inc
shared/rabbitMQLib.inc
shared/get_host_info.inc
shared/host.ini
"
    ;;
  dmz)
    REMOTE_SUBDIR="dmz"
    FILES="
dmz/dmzMovieWorker.php
dmz/movieRabbitMQ.ini
shared/path.inc
shared/rabbitMQLib.inc
shared/get_host_info.inc
shared/host.ini
"
    ;;
  *)
    echo "Error: Unknown bundle '$BUNDLE'"
    exit 1
    ;;
esac

# Project Root
cd "$PROJECT_ROOT"

# Generate versions
VERSION="$(date +%Y%m%d%H%M%S)"
ARCHIVE="${BUNDLE}_${VERSION}.tar.gz"

echo "Creating bundle: $ARCHIVE"


tar czf "$ARCHIVE" $FILES

# Upload to deployment server
REMOTE_DIR="${DEPLOY_BASE_DIR}/${REMOTE_SUBDIR}"

ssh "${DEPLOY_USER}@${DEPLOY_HOST}" "mkdir -p '${REMOTE_DIR}'"
scp "$ARCHIVE" "${DEPLOY_USER}@${DEPLOY_HOST}:${REMOTE_DIR}/"

# Register bundle on deployment server
ssh "${DEPLOY_USER}@${DEPLOY_HOST}" \
  "/home/IT490/deploy/register_bundle.sh '${BUNDLE}' '${VERSION}' '${ARCHIVE}'"

echo "Bundle complete."
