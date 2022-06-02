# Compressing, Cacheing and Git Hooks

These programs are no longer used. These were on the original SEMLA website, which built static html pages. Since we obviously want to send smaller, compressed files to the clients we gzipped all files whenever they changed, and served those if possible. Now mod_deflate is available, and most pages are created in WordPress, this strategy doesn't really add much - so we now just use mod_deflate to compress pages on the fly. It adds slightly to CPU load, but it does have the advantage that we won't ever get a `file.css.gz` being a different version to `file.css` if the compression program is not run.

In this directory are also several files which also deal with creating `.gz` versions of cached files, e.g. for fixtures calendars. These are kept here as they will need to be reinstated if we change back to using this.

See notes at the bottom to set this up.

These files are git hooks that are executed on the WordPress server.

There are 2 scripts - `post-checkout` and `post-merge`, which both do the same thing - compress all text files to a gzipped version `file.gz`, so that they can be served compressed, which reduces file size in order to transmit faster and save bandwidth.

`post-chceckout` is executed after a `git checkout`, and `post-merge` is executed after a merge, which also happens after a `git pull`.

The effect of this is that the scripts execute whenever the WordPress source is updated through git.

Hook files need to be placed in `.git/hooks` on the server, and make sure permissions include `x` so it can be executed.

## Setup

In order for the .gz files to be served the following needs to be added to the root `.htaccess` file.

```apache
RemoveType .gz
AddEncoding gzip .gz
<IfModule mod_headers.c>
  <FilesMatch ".(css|doc|gpx|html|ico|ics|js|svg|xls|gz)$">
    Header append Vary Accept-Encoding
  </FilesMatch>
</IfModule>
```

This ensures the .gz files are served with the correct file type, rather than content-type gzip, and we send the correct headers.

To serve the compressed version of the file add the following to the root `.htaccess` file.
  
```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /

RewriteCond %{HTTP:Accept-Encoding} gzip
RewriteCond %{REQUEST_FILENAME} !\.gz$
RewriteCond %{REQUEST_FILENAME}.gz -f
RewriteRule ^(.*)$ $1.gz [L]
</IfModule>
```

This rewrites the `filename` to `filename.gz` if that file exists, and the client accepts gzip encoding.

The `RewriteCond` and `RewriteRule` lines should also be copied into any other `.htaccess` files which have `RewriteEngine On`.
