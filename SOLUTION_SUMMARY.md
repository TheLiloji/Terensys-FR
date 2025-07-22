# terensys.fr Website Issues - Complete Solution Guide

## 🔍 **Confirmed Issues**

1. ✅ **INFINITE REDIRECT LOOP**: `https://www.terensys.fr/` redirects to itself indefinitely
2. ✅ **SSL CERTIFICATE ISSUES**: `https://terensys.fr/` has TLS connection problems  
3. ✅ **MAXREDIRECTS ERRORS**: Browser/client applications hitting redirect limits
4. ✅ **EPROTO ERRORS**: Protocol errors on non-www domain

**Server Details:**
- IP: 188.165.192.106
- Stack: Caddy (proxy) + Apache/2.4.52 (Ubuntu)
- Current Status: Both domains affected

---

## 🚨 **Immediate Action Required**

### **Root Cause**
The server has a redirect rule that redirects `https://www.terensys.fr/` back to `https://www.terensys.fr/`, creating an infinite loop.

### **Evidence**
```bash
curl -I -L --max-redirs 10 https://www.terensys.fr/
# Shows 10 identical 301 redirects, all pointing to the same URL
```

---

## 🛠️ **Solution Options**

### **Option 1: Fix Apache .htaccess (Recommended)**

Replace the current `.htaccess` file with the corrected version:

```apache
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]

# Force www (redirect non-www to www)
RewriteCond %{HTTP_HOST} ^terensys\.fr$ [NC]
RewriteRule ^(.*)$ https://www.terensys.fr/$1 [R=301,L]

# Remove trailing slashes to prevent loops
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)/$ /$1 [R=301,L]

# CRITICAL: Remove any rules like this:
# RewriteRule ^(.*)$ https://www.terensys.fr/ [R=301,L]
```

### **Option 2: Fix Caddy Configuration**

Update the Caddy config file (usually `/etc/caddy/Caddyfile`):

```caddy
# Redirect non-www to www
terensys.fr {
    redir https://www.terensys.fr{uri} permanent
}

# Redirect HTTP to HTTPS
http://www.terensys.fr {
    redir https://www.terensys.fr{uri} permanent
}

# Main site
www.terensys.fr {
    tls {
        protocols tls1.2 tls1.3
    }
    
    header {
        Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
        X-Content-Type-Options "nosniff"
        X-Frame-Options "SAMEORIGIN"
    }
    
    # Choose one:
    root * /var/www/html           # For static files
    # reverse_proxy localhost:8080 # For Apache backend
    file_server
}
```

---

## 📋 **Step-by-Step Fix Process**

### **Step 1: Backup Current Configuration**
```bash
# Backup Apache config
cp /var/www/html/.htaccess /var/www/html/.htaccess.backup

# Backup Caddy config  
cp /etc/caddy/Caddyfile /etc/caddy/Caddyfile.backup
```

### **Step 2: Identify the Problem Rule**
Look for rules like this that cause the loop:
```apache
RewriteRule ^(.*)$ https://www.terensys.fr/ [R=301,L]
```
This redirects everything to the root URL with trailing slash, causing the loop.

### **Step 3: Apply the Fix**
- **For Apache**: Update `.htaccess` with the corrected rules
- **For Caddy**: Update `Caddyfile` with the corrected configuration

### **Step 4: Reload Configuration**
```bash
# For Apache
systemctl reload apache2

# For Caddy
systemctl reload caddy
```

### **Step 5: Test the Fix**
```bash
# Should show successful response (200) with no redirect loop
curl -I -L --max-redirs 5 https://www.terensys.fr/

# Should redirect to www version
curl -I -L --max-redirs 5 https://terensys.fr/

# Detailed test
curl -s -o /dev/null -w "Status: %{http_code}\nFinal URL: %{url_effective}\nRedirects: %{num_redirects}\n" https://www.terensys.fr/
```

---

## ✅ **Expected Results After Fix**

| URL | Expected Behavior |
|-----|------------------|
| `http://terensys.fr/` | 301 → `https://www.terensys.fr/` |
| `https://terensys.fr/` | 301 → `https://www.terensys.fr/` |
| `http://www.terensys.fr/` | 308 → `https://www.terensys.fr/` |
| `https://www.terensys.fr/` | 200 (final destination) |

---

## 🔄 **Verification Commands**

```bash
# Test all URL variations
curl -I -L --max-redirs 3 http://terensys.fr/
curl -I -L --max-redirs 3 https://terensys.fr/
curl -I -L --max-redirs 3 http://www.terensys.fr/
curl -I -L --max-redirs 3 https://www.terensys.fr/

# Get detailed response info
curl -s -o /dev/null -w "HTTP %{http_code} | Redirects: %{num_redirects} | Final: %{url_effective}\n" https://www.terensys.fr/
```

---

## 🚀 **Priority Level: CRITICAL**

This issue prevents the website from loading completely and affects:
- ❌ User experience (site completely inaccessible)
- ❌ SEO rankings (search engines can't index)
- ❌ Business operations (lost traffic and customers)
- ❌ SSL security (certificate issues on non-www)

**Estimated Fix Time:** 15-30 minutes once server access is available.

---

## 📞 **Need Help?**

If you need assistance implementing these fixes:
1. Check server access permissions
2. Verify which configuration system is primary (Apache vs Caddy)
3. Test in staging environment first if available
4. Monitor logs during implementation

**Files to check:**
- `/var/www/html/.htaccess` (Apache)
- `/etc/caddy/Caddyfile` (Caddy)
- Apache virtual host configs in `/etc/apache2/sites-available/`