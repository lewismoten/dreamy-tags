#!/bin/bash

PLUGIN_DIR="dreamy-tags"
MAIN_FILE="$PLUGIN_DIR/$PLUGIN_DIR.php"
BUILD_DIR="dist_temp"
VERSION_FILE="version.txt"
CHANGELOG_FILE="CHANGELOG.md"
README_FILE="$PLUGIN_DIR/readme.txt"

if [ ! -f "$VERSION_FILE" ]; then
    echo "Error: version.txt not found!"
    exit 1
fi

CHANGELOG_ENTRIES=()
while getopts ":c:" opt; do
    case $opt in
        c)
            if [ ${#OPTARG} -lt 8 ]; then
                echo "Error: Changelog entry must be at least 8 characters: \"$OPTARG\""
                exit 1
            fi
            CHANGELOG_ENTRIES+=("$OPTARG")
            ;;
        \?)
            echo "Error: Invalid option -$OPTARG"
            echo "Usage: $0 -c \"Change 1\" -c \"Change 2\" ..."
            exit 1
            ;;
        :)
            echo "Error: Option -$OPTARG requires an argument."
            exit 1
            ;;
    esac
done
shift $((OPTIND - 1))
if [ "$#" -gt 0 ]; then
    for entry in "$@"; do
        if [ ${#entry} -lt 8 ]; then
            echo "Error: Changelog entry must be at least 8 characters: \"$entry\""
            exit 1
        fi
        CHANGELOG_ENTRIES+=("$entry")
    done
fi
if [ "${#CHANGELOG_ENTRIES[@]}" -eq 0 ]; then
    echo "Error: At least one changelog entry is required."
    echo "Usage: $0 -c \"Fix A\" -c \"Fix B\""
    exit 1
fi

CURRENT_VERSION=$(cat "$VERSION_FILE" | tr -d '[:space:]')
BASE_VERSION=$(echo $CURRENT_VERSION | cut -d. -f1-2)
PATCH_VERSION=$(echo $CURRENT_VERSION | cut -d. -f3)
NEW_PATCH=$((PATCH_VERSION + 1))
VERSION="${BASE_VERSION}.${NEW_PATCH}"
echo "$VERSION" > "$VERSION_FILE"
echo "Version bumped: $CURRENT_VERSION -> $VERSION"

if [ ! -f "$README_FILE" ]; then
    echo "Error: readme.txt not found at $README_FILE"
    exit 1
fi
if grep -q "^= $VERSION =" "$README_FILE"; then
    echo "Error: Version $VERSION already exists in $README_FILE"
    exit 1
fi

if [ ! -f "$README_FILE" ]; then
    echo "Error: readme.txt not found at $README_FILE"
    exit 1
fi

if ! grep -q "^== Changelog ==$" "$README_FILE"; then
    echo "Error: '== Changelog ==' section not found in $README_FILE"
    exit 1
fi

if grep -q "^= $VERSION =$" "$README_FILE"; then
    echo "Error: Version $VERSION already exists in $README_FILE"
    exit 1
fi

tmp="$(mktemp)"

awk -v version="$VERSION" '
BEGIN { inserted=0 }
{
    print $0
    if (!inserted && $0 ~ /^== Changelog ==$/) {
        print ""
        print "= " version " ="
        inserted=1
    }
}
END {
    if (!inserted) exit 2
}
' "$README_FILE" > "$tmp"

tmp2="$(mktemp)"
inserted_entries=0

while IFS= read -r line; do
    echo "$line" >> "$tmp2"

    if [ "$inserted_entries" -eq 0 ] && [ "$line" = "= $VERSION =" ]; then
        for entry in "${CHANGELOG_ENTRIES[@]}"; do
            echo "* $entry" >> "$tmp2"
        done
        echo "" >> "$tmp2"
        inserted_entries=1
    fi
done < "$tmp"

rm -f "$tmp"
mv "$tmp2" "$README_FILE"

# Update Changelog
DATE=$(date +"%Y-%m-%d")
echo "## $VERSION - $DATE" >> "$CHANGELOG_FILE"
for entry in "${CHANGELOG_ENTRIES[@]}"; do
    echo "- $entry" >> "$CHANGELOG_FILE"
done
echo "" >> "$CHANGELOG_FILE"

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
