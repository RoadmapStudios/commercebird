#!/usr/bin/env sh
PLUGIN_SLUG="$(basename "$PWD")"
PROJECT_PATH=$(pwd)
BUILD_PATH="${PROJECT_PATH}/build"
DEST_PATH="${BUILD_PATH}/${PLUGIN_SLUG}"

# Function to display progress messages
progress_message() {
  local message="$1"
  local color_green="\033[32m"
  local color_reset="\033[0m"
  echo -e "[$(date +'%Y-%m-%d %H:%M:%S')] ${color_green}${message}${color_reset}"
}

# Abort on errors
set -e

# Prepare build directory
progress_message "Preparing build directory..."
rm -rf "$BUILD_PATH"
rm -rf "$PLUGIN_SLUG".zip
mkdir -p "$DEST_PATH"
mkdir -p "$BUILD_PATH"

echo "PROJECT_PATH: $PROJECT_PATH"
echo "BUILD_PATH: $BUILD_PATH"
echo "DEST_PATH: $DEST_PATH"

# cd "$PROJECT_PATH"
progress_message "DEBUG: Listing project path..."
cd "$PROJECT_PATH"
ls -l "$PROJECT_PATH"

# Install npm dependencies and build assets
progress_message "Building admin template..."
npm --prefix ./admin/assets/ install
npm --prefix ./admin/assets/ run build-only

progress_message "DEBUG: Checking if .rsyncignore exists..."
ls -l "$PROJECT_PATH/.rsyncignore"

progress_message "Copying files for production..."
rsync -av --exclude-from="$PROJECT_PATH/.rsyncignore" "$PROJECT_PATH/" "$DEST_PATH/"
if [ -d "$PROJECT_PATH/admin/assets/dist" ]; then
    progress_message "DEBUG: Source dist folder exists, proceeding with copy..."
    mkdir -p "$DEST_PATH/admin/assets/dist"
    # rsync -av --progress "$PROJECT_PATH/admin/assets/dist/" "$DEST_PATH/admin/assets/"
    cp -r "$PROJECT_PATH/admin/assets/dist" "$DEST_PATH/admin/assets/"
    progress_message "DEBUG: Checking if files are copied to the destination..."
    ls -l "$DEST_PATH/admin/assets/dist/"
else
    progress_message "ERROR: Source dist folder does not exist!"
    ls -l "$PROJECT_PATH/admin/assets"
    exit 1
fi

# Modify `index.js` to remove .mp3 URLs (Linux-compatible sed)
INDEX_JS_PATH="$DEST_PATH/admin/assets/dist/index.js"
chmod +w "$INDEX_JS_PATH"
if [ -f "$INDEX_JS_PATH" ]; then
    progress_message "Modifying index.js to remove lines with .mp3 URLs..."
    sed -i 's/n\.src="https:\/\/[^"]*\.mp3"/n.src=""/g' "$INDEX_JS_PATH"
fi

# Install PHP dependencies
progress_message "Installing PHP dependencies..."
composer install --working-dir="$DEST_PATH" --no-dev
rm "$DEST_PATH/composer.lock"

# Remove dev data
progress_message "Removing dev data..."
chmod +w "$DEST_PATH/admin/includes/Template.php"
sed -i '74,77d' "$DEST_PATH/admin/includes/Template.php"

# Add index.php to every directory
progress_message "Adding index.php to every directory..."
find "$DEST_PATH" -type d -exec sh -c "echo '<?php // silence' > {}/index.php" \;

# Completion message
progress_message "Build process completed successfully."
exit