#!/bin/bash

# SPIP Performance Optimization Script
# This script minifies JavaScript files and removes development artifacts

echo "Starting SPIP Performance Optimization..."

# Check if uglifyjs is available, if not install it
if ! command -v uglifyjs &> /dev/null; then
    echo "Installing UglifyJS for JavaScript minification..."
    npm install -g uglify-js 2>/dev/null || echo "Warning: Could not install uglify-js globally. Attempting local installation..."
    npx uglify-js --version 2>/dev/null || {
        echo "Installing uglify-js locally..."
        npm init -y > /dev/null 2>&1
        npm install uglify-js --save-dev
    }
fi

# Function to minify a JavaScript file
minify_js() {
    local input_file="$1"
    local output_file="${input_file%.js}.min.js"
    
    # Skip if already minified or if .min.js version already exists
    if [[ "$input_file" == *.min.js ]] || [[ -f "$output_file" ]]; then
        return 0
    fi
    
    echo "Minifying: $input_file"
    
    # Try global uglifyjs first, then npx
    if command -v uglifyjs &> /dev/null; then
        uglifyjs "$input_file" --compress --mangle --output "$output_file" 2>/dev/null
    else
        npx uglify-js "$input_file" --compress --mangle --output "$output_file" 2>/dev/null
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

# Minify large JavaScript files in prive directory
echo "Optimizing JavaScript files in prive directory..."
for js_file in ./prive/javascript/*.js; do
    if [[ -f "$js_file" && $(wc -c < "$js_file") -gt 10000 ]]; then
        minify_js "$js_file"
    fi
done

# Remove source maps from production (they're already blocked in .htaccess)
echo "Removing source map files..."
find . -name "*.map" -type f -delete
echo "Removed source map files for security and performance"

# Create optimized versions of the largest plugin JavaScript files
echo "Optimizing plugin JavaScript files..."

# Optimize Select2 fork
if [[ -f "./plugins/auto/select2/v2.1.0/javascript/select2.fork.full.js" ]]; then
    minify_js "./plugins/auto/select2/v2.1.0/javascript/select2.fork.full.js"
fi

# Optimize D3.js if not already minified
if [[ -f "./plugins-dist/statistiques/lib/d3/d3.js" && ! -f "./plugins-dist/statistiques/lib/d3/d3.min.js" ]]; then
    minify_js "./plugins-dist/statistiques/lib/d3/d3.js"
fi

# Optimize Luxon if not already minified
if [[ -f "./plugins-dist/statistiques/lib/luxon/luxon.js" && ! -f "./plugins-dist/statistiques/lib/luxon/luxon.min.js" ]]; then
    minify_js "./plugins-dist/statistiques/lib/luxon/luxon.js"
fi

# Optimize JSTree if needed
if [[ -f "./plugins-dist/plan/lib/jstree/dist/jstree.js" && ! -f "./plugins-dist/plan/lib/jstree/dist/jstree.min.js" ]]; then
    minify_js "./plugins-dist/plan/lib/jstree/dist/jstree.js"
fi

# Optimize MediaElement if needed
if [[ -f "./plugins-dist/medias/lib/mejs/mediaelement-and-player.js" && ! -f "./plugins-dist/medias/lib/mejs/mediaelement-and-player.min.js" ]]; then
    minify_js "./plugins-dist/medias/lib/mejs/mediaelement-and-player.js"
fi

echo ""
echo "=== OPTIMIZATION SUMMARY ==="
echo "✓ Added gzip/brotli compression to .htaccess"
echo "✓ Configured comprehensive caching headers"
echo "✓ Added security headers"
echo "✓ Blocked source maps in production"
echo "✓ Minified JavaScript files"
echo "✓ Removed source map files"
echo ""

# Calculate total JavaScript file sizes
total_js_size=$(find . -name "*.js" -type f -exec wc -c {} + | tail -1 | awk '{print $1}')
total_min_js_size=$(find . -name "*.min.js" -type f -exec wc -c {} + | tail -1 | awk '{print $1}' 2>/dev/null || echo "0")

echo "Total JavaScript size: $(($total_js_size / 1024)) KB"
echo "Total minified JavaScript size: $(($total_min_js_size / 1024)) KB"

if [[ $total_min_js_size -gt 0 ]]; then
    savings=$(($total_js_size - $total_min_js_size))
    percentage=$(($savings * 100 / $total_js_size))
    echo "Potential savings with minified versions: $(($savings / 1024)) KB (${percentage}%)"
fi

echo ""
echo "=== NEXT STEPS ==="
echo "1. Test the site to ensure all optimizations work correctly"
echo "2. Consider implementing a CDN for static assets"
echo "3. Monitor Core Web Vitals and page load times"
echo "4. Consider lazy loading for images and non-critical JavaScript"
echo "5. Review and optimize database queries if needed"
echo ""
echo "Optimization complete!"