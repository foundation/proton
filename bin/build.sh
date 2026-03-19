#!/bin/bash
set -e

# Usage: ./bin/build.sh [version]
# If no version is provided, uses the latest git tag.

ROOT=$(git rev-parse --show-toplevel)
cd "$ROOT"

VERSION=${1:-$(git describe --tags --abbrev=0 2>/dev/null)}

if [ -z "$VERSION" ]; then
    echo "Error: No version provided and no git tags found."
    echo "Usage: ./bin/build.sh <version>"
    exit 1
fi

echo "==> Building Proton $VERSION"

# Set the version in config/app.php
echo "==> Setting version to $VERSION..."
sed -i '' "s/'version' => app('git.version')/'version' => '${VERSION}'/" config/app.php

# Install production dependencies
echo "==> Installing production dependencies..."
composer install --no-dev --quiet

# Build the phar
echo "==> Compiling phar..."
box compile

# Restore dev dependencies
echo "==> Restoring dev dependencies..."
composer install --quiet

# Restore dynamic version in config/app.php
echo "==> Restoring config/app.php..."
git checkout config/app.php

# Verify
echo "==> Verifying build..."
php builds/proton --version

echo "==> Build complete: builds/proton"
