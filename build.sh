#!/bin/bash

PLUGIN_DIR="dreamy-tags"
MAIN_FILE="$PLUGIN_DIR/$PLUGIN_DIR.php"
BUILD_DIR="dist_temp"
VERSION_FILE="version.txt"

if [ ! -f "$VERSION_FILE" ]; then
    echo "Error: version.txt not found!"
    exit 1
fi

CURRENT_VERSION=$(cat "$VERSION_FILE" | tr -d '[:space:]')
BASE_VERSION=$(echo $CURRENT_VERSION | cut -d. -f1-2)
PATCH_VERSION=$(echo $CURRENT_VERSION | cut -d. -f3)
NEW_PATCH=$((PATCH_VERSION + 1))
VERSION="${BASE_VERSION}.${NEW_PATCH}"
echo "$VERSION" > "$VERSION_FILE"
echo "Version bumped: $CURRENT_VERSION -> $VERSION"

rm -rf "$BUILD_DIR"
rm -f *.zip
mkdir -p "$BUILD_DIR/$PLUGIN_DIR"
cp -R "$PLUGIN_DIR/" "$BUILD_DIR/$PLUGIN_DIR/"

echo "Injecting version $VERSION into files..."
sed -i '' "s/Version:           .*/Version:           $VERSION/" "$BUILD_DIR/$PLUGIN_DIR/$PLUGIN_DIR.php"
sed -i '' "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" "$BUILD_DIR/$PLUGIN_DIR/block.json"

echo "Standardizing file encodings..."
find "$BUILD_DIR/$PLUGIN_DIR" -type f \( -name "*.php" -o -name "*.css" -o -name "*.txt" -o -name "*.json" \) | while read -r file; do
    temp_file="${file}.tmp"
    iconv -f UTF-8 -t UTF-8//IGNORE "$file" > "$temp_file"
    mv "$temp_file" "$file"
done

echo "Zipping distribution..."
ZIP_NAME="${PLUGIN_DIR}-v${VERSION}.zip"
cd "$BUILD_DIR"
zip -rX9 "../$ZIP_NAME" "$PLUGIN_DIR" -x "*.DS_Store" -x "__MACOSX" -x "*/.DS_Store"
cd ..

echo "Cleanup"
rm -rf "$BUILD_DIR"

echo "------------------------------------------"
echo "Success! Created: $ZIP_NAME"
