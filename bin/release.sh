#!/bin/bash
set -e

# Usage: ./release.sh <version>
# Example: ./release.sh 0.7.0

VERSION=$1

if [ -z "$VERSION" ]; then
    echo "Usage: ./release.sh <version>"
    echo "Example: ./release.sh 0.7.0"
    exit 1
fi

TAG="v${VERSION}"

# Ensure we're on develop and it's clean
BRANCH=$(git branch --show-current)
if [ "$BRANCH" != "develop" ]; then
    echo "Error: Must be on the develop branch. Currently on: $BRANCH"
    exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
    echo "Error: Working directory is not clean. Commit or stash your changes first."
    exit 1
fi

# Check tag doesn't already exist
if git rev-parse "$TAG" >/dev/null 2>&1; then
    echo "Error: Tag $TAG already exists."
    exit 1
fi

echo "==> Releasing Proton $TAG"

# Create release branch
echo "==> Creating release branch: release/$TAG"
git checkout -b "release/$TAG"

# Set the version in config/app.php for the phar build
echo "==> Setting version to $TAG..."
sed -i '' "s/'version' => app('git.version')/'version' => '${TAG}'/" config/app.php

# Build the phar
echo "==> Installing production dependencies..."
composer install --no-dev --quiet

echo "==> Building phar..."
box compile

# Restore dev dependencies for the repo
echo "==> Restoring dev dependencies..."
composer install --quiet

# Restore dynamic version in config/app.php
echo "==> Restoring dynamic version in config..."
git checkout config/app.php

# Commit the built phar
echo "==> Committing built phar..."
git add builds/proton.phar
git commit -m "$TAG build"

# Merge into master
echo "==> Merging into master..."
git checkout master
git merge --no-ff "release/$TAG" -m "Merge branch 'release/$TAG'"

# Tag the release
echo "==> Tagging $TAG..."
git tag -a "$TAG" -m "$TAG"

# Merge back into develop
echo "==> Merging back into develop..."
git checkout develop
git merge --no-ff "release/$TAG" -m "Merge branch 'release/$TAG' into develop"

# Delete the release branch
echo "==> Cleaning up release branch..."
git branch -d "release/$TAG"

# Push everything
echo "==> Pushing to remote..."
git push origin master develop --tags

echo "==> Done! Proton $TAG has been released."
