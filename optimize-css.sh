#!/bin/bash

# CSS Optimization Script for SPIP
# This script minifies CSS files and removes unnecessary whitespace

echo "Starting CSS Optimization..."

# Check if csso-cli is available, if not try to install it
if ! command -v csso &> /dev/null; then
    echo "Installing CSSO for CSS minification..."
    npm install -g csso-cli 2>/dev/null || {
        echo "Installing csso-cli locally..."
        npm init -y > /dev/null 2>&1
        npm install csso-cli --save-dev
    }
fi

# Function to minify a CSS file
minify_css() {
    local input_file="$1"
    local output_file="${input_file%.css}.min.css"
    
    # Skip if already minified or if .min.css version already exists
    if [[ "$input_file" == *.min.css ]] || [[ -f "$output_file" ]]; then
        return 0
    fi
    
    echo "Minifying: $input_file"
    
    # Try global csso first, then npx
    if command -v csso &> /dev/null; then
        csso "$input_file" --output "$output_file" 2>/dev/null
    else
        npx csso "$input_file" --output "$output_file" 2>/dev/null
    fi
    
    if [[ -f "$output_file" ]]; then
        local original_size=$(wc -c < "$input_file")
        local minified_size=$(wc -c < "$output_file")
        local savings=$((original_size - minified_size))
        local percentage=$((savings * 100 / original_size))
        echo "  Saved: ${savings} bytes (${percentage}% reduction)"
    else
        echo "  Warning: Failed to minify $input_file"
    fi
}

# Optimize CSS files in prive themes
echo "Optimizing CSS files in prive themes..."
for css_file in ./prive/themes/spip/*.css; do
    if [[ -f "$css_file" && $(wc -c < "$css_file") -gt 5000 ]]; then
        minify_css "$css_file"
    fi
done

# Optimize plugin CSS files
echo "Optimizing plugin CSS files..."
for css_file in $(find ./plugins ./plugins-dist -name "*.css" -size +10k | head -20); do
    minify_css "$css_file"
done

echo "CSS optimization complete!"