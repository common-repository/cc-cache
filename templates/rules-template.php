<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
<?php if ( $blogs ) : foreach( $blogs as $blog ) : $blog = (array)$blog; ?>
RewriteCond %{QUERY_STRING} !(^|&)cache=false($|&)
RewriteCond %{HTTP:cache} !false
RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$
RewriteCond %{HTTP_HOST} <?= $blog['domain']; ?>$ [NC]
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{DOCUMENT_ROOT}/wp-content/cache/<?= $blog['site_id']; ?>/<?= $blog['blog_id']; ?>/$1/index.html -f
RewriteRule (.*) /wp-content/cache/<?= $blog['site_id']; ?>/<?= $blog['blog_id']; ?>/$1/index.html [L]
RewriteCond %{QUERY_STRING} !(^|&)cache=false($|&)
RewriteCond %{HTTP:cache} !false
RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$
RewriteCond %{HTTP_HOST} <?= $blog['domain']; ?>$ [NC]
RewriteCond %{DOCUMENT_ROOT}/wp-content/cache/<?= $blog['site_id']; ?>/<?= $blog['blog_id']; ?>/index.html -f
RewriteRule ^$ /wp-content/cache/<?= $blog['site_id']; ?>/<?= $blog['blog_id']; ?>/index.html [L]
<?php endforeach; else: ?>
RewriteCond %{QUERY_STRING} !(^|&)cache=false($|&)
RewriteCond %{HTTP:cache} !false
RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{DOCUMENT_ROOT}/wp-content/cache/$1/index.html -f
RewriteRule (.*) /wp-content/cache/$1/index.html [L]
RewriteCond %{QUERY_STRING} !(^|&)cache=false($|&)
RewriteCond %{HTTP:cache} !false
RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$
RewriteCond %{DOCUMENT_ROOT}/wp-content/cache/index.html -f
RewriteRule ^$ /wp-content/cache/index.html [L]
<?php endif; ?>
</IfModule>