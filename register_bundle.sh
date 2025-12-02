set -e
NAME="$1"
VERSION="$2"
ARCHIVE="$3"
mysql -udeployuser -pSecure123 deploydb <<EOF
INSERT INTO bundles (name,version,archive,status)
VALUES('$NAME','$VERSION','$ARCHIVE','new');
EOF
