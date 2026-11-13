<?php
return function(PDO $pdo): void {
    $uploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "uploads";
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0755, true);
    }

    $htaccessPath = $uploadsDir . DIRECTORY_SEPARATOR . ".htaccess";
    $content = <<<HTACCESS
# Block PHP execution in uploads directory
<FilesMatch "(?i)\.(php|phtml|php3|php4|php5|php7|php8|phar|inc|cgi|pl|sh)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

<IfModule mod_php.c>
    php_flag engine off
</IfModule>
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>
<IfModule mod_php8.c>
    php_flag engine off
</IfModule>

Options -ExecCGI -Indexes
HTACCESS;

    @file_put_contents($htaccessPath, $content);
};

