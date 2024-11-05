#!/usr/bin/env sh
PLUGIN_SLUG="$(basename $PWD)"
PROJECT_PATH=$(pwd)
BUILD_PATH="${PROJECT_PATH}/build"
DEST_PATH="$BUILD_PATH/$PLUGIN_SLUG"

# Function to display progress messages
progress_message() {
  local message="$1"

  # Define color codes
  local color_reset="\033[0m"
  local color_green="\033[32m"

  # Print the colorized message
  echo -e "[$(date +'%Y-%m-%d %H:%M:%S')] ${color_green}${message}${color_reset}"
}

# abort on errors
set -e

# prepare place for build.
progress_message "Preparing build directory..."
rm -rf "$BUILD_PATH"
rm -rf "$PLUGIN_SLUG".zip
mkdir -p "$DEST_PATH"

# build assets
progress_message "Building admin template..."
npm --prefix ./admin/assets/ run build-only

# copy all files for production
progress_message "Copying files for production..."
rsync -rc --exclude-from="$PROJECT_PATH/.distignore" "$PROJECT_PATH/" "$DEST_PATH/" --delete --delete-excluded
rsync -rc "$PROJECT_PATH/admin/assets/dist" "$DEST_PATH/admin/assets"

## Install PHP dependencies
progress_message "Installing PHP dependencies..."
composer install --working-dir="$DEST_PATH" --no-dev
rm "$DEST_PATH"/composer.lock

progress_message "Removing dev data..."
sed -i '' '70,73d' "$DEST_PATH"/admin/includes/Template.php
## Add index.php to every directory
progress_message "Adding index.php to every directory..."
find "$DEST_PATH" -type d -exec sh -c "echo '<?php // silence' > {}/index.php" \;

## Create zip archive
progress_message "Creating zip archive..."
cd "$BUILD_PATH" || exit
zip -q -r "${PLUGIN_SLUG}.zip" "$PLUGIN_SLUG/"

cd "$PROJECT_PATH" || exit
mv "$BUILD_PATH/${PLUGIN_SLUG}.zip" "$PROJECT_PATH"

# Completion message
progress_message "Build process completed successfully."
exit
