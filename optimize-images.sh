#!/bin/bash

# Image Optimization Script for SPIP
# This script optimizes JPEG and PNG images and creates WebP versions

echo "Starting Image Optimization..."

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Install image optimization tools if available
install_tools() {
    echo "Checking for image optimization tools..."
    
    # Check for cwebp (WebP encoder)
    if ! command_exists cwebp; then
        echo "WebP tools not found. Please install webp tools:"
        echo "  Ubuntu/Debian: sudo apt-get install webp"
        echo "  CentOS/RHEL: sudo yum install libwebp-tools"
        echo "  macOS: brew install webp"
    fi
    
    # Check for jpegoptim
    if ! command_exists jpegoptim; then
        echo "jpegoptim not found. Please install:"
        echo "  Ubuntu/Debian: sudo apt-get install jpegoptim"
        echo "  CentOS/RHEL: sudo yum install jpegoptim"
        echo "  macOS: brew install jpegoptim"
    fi
    
    # Check for optipng
    if ! command_exists optipng; then
        echo "optipng not found. Please install:"
        echo "  Ubuntu/Debian: sudo apt-get install optipng"
        echo "  CentOS/RHEL: sudo yum install optipng"
        echo "  macOS: brew install optipng"
    fi
}

# Function to optimize JPEG files
optimize_jpeg() {
    local file="$1"
    local original_size=$(wc -c < "$file")
    
    if command_exists jpegoptim; then
        echo "Optimizing JPEG: $file"
        jpegoptim --strip-all --max=85 "$file" >/dev/null 2>&1
        local new_size=$(wc -c < "$file")
        local savings=$((original_size - new_size))
        local percentage=$((savings * 100 / original_size))
        if [ $savings -gt 0 ]; then
            echo "  Saved: ${savings} bytes (${percentage}% reduction)"
        fi
    fi
    
    # Create WebP version if possible
    if command_exists cwebp; then
        local webp_file="${file%.*}.webp"
        if [ ! -f "$webp_file" ]; then
            echo "Creating WebP: $webp_file"
            cwebp -q 85 "$file" -o "$webp_file" >/dev/null 2>&1
            if [ -f "$webp_file" ]; then
                local webp_size=$(wc -c < "$webp_file")
                local webp_savings=$((original_size - webp_size))
                local webp_percentage=$((webp_savings * 100 / original_size))
                echo "  WebP saved: ${webp_savings} bytes (${webp_percentage}% vs original)"
            fi
        fi
    fi
}

# Function to optimize PNG files
optimize_png() {
    local file="$1"
    local original_size=$(wc -c < "$file")
    
    if command_exists optipng; then
        echo "Optimizing PNG: $file"
        optipng -quiet -o2 "$file" 2>/dev/null
        local new_size=$(wc -c < "$file")
        local savings=$((original_size - new_size))
        local percentage=$((savings * 100 / original_size))
        if [ $savings -gt 0 ]; then
            echo "  Saved: ${savings} bytes (${percentage}% reduction)"
        fi
    fi
    
    # Create WebP version if possible
    if command_exists cwebp; then
        local webp_file="${file%.*}.webp"
        if [ ! -f "$webp_file" ]; then
            echo "Creating WebP: $webp_file"
            cwebp -q 90 "$file" -o "$webp_file" >/dev/null 2>&1
            if [ -f "$webp_file" ]; then
                local webp_size=$(wc -c < "$webp_file")
                local webp_savings=$((original_size - webp_size))
                local webp_percentage=$((webp_savings * 100 / original_size))
                echo "  WebP saved: ${webp_savings} bytes (${webp_percentage}% vs original)"
            fi
        fi
    fi
}

# Install tools check
install_tools

# Get initial size
initial_size=$(du -sb ./IMG 2>/dev/null | cut -f1)
echo "Initial IMG directory size: $((initial_size / 1024 / 1024)) MB"

# Count files to process
jpeg_count=$(find ./IMG -type f \( -iname "*.jpg" -o -iname "*.jpeg" \) | wc -l)
png_count=$(find ./IMG -type f -iname "*.png" | wc -l)

echo "Found $jpeg_count JPEG files and $png_count PNG files to optimize"

# Process JPEG files
if [ $jpeg_count -gt 0 ]; then
    echo ""
    echo "Processing JPEG files..."
    find ./IMG -type f \( -iname "*.jpg" -o -iname "*.jpeg" \) | while read -r file; do
        optimize_jpeg "$file"
    done
fi

# Process PNG files  
if [ $png_count -gt 0 ]; then
    echo ""
    echo "Processing PNG files..."
    find ./IMG -type f -iname "*.png" | while read -r file; do
        optimize_png "$file"
    done
fi

# Calculate final statistics
final_size=$(du -sb ./IMG 2>/dev/null | cut -f1)
total_savings=$((initial_size - final_size))
percentage_saved=$((total_savings * 100 / initial_size))

echo ""
echo "=== IMAGE OPTIMIZATION RESULTS ==="
echo "Initial size: $((initial_size / 1024 / 1024)) MB"
echo "Final size: $((final_size / 1024 / 1024)) MB"
echo "Total saved: $((total_savings / 1024 / 1024)) MB (${percentage_saved}%)"

# Count WebP files created
webp_count=$(find ./IMG -type f -iname "*.webp" | wc -l)
if [ $webp_count -gt 0 ]; then
    echo "WebP files created: $webp_count"
    echo ""
    echo "=== WEBP USAGE RECOMMENDATIONS ==="
    echo "1. Update templates to serve WebP when supported:"
    echo "   <picture>"
    echo "     <source srcset=\"image.webp\" type=\"image/webp\">"
    echo "     <img src=\"image.jpg\" alt=\"...\">"
    echo "   </picture>"
    echo ""
    echo "2. Add .htaccess rules for automatic WebP serving:"
    echo "   RewriteCond %{HTTP_ACCEPT} image/webp"
    echo "   RewriteCond %{REQUEST_FILENAME} (.*)\.(jpe?g|png)$"
    echo "   RewriteCond %1.webp -f"
    echo "   RewriteRule (.*)\.(jpe?g|png)$ %1.webp [T=image/webp,E=accept:1]"
fi

echo ""
echo "Image optimization complete!"