FSyncMS nginx config
====================

FSyncMS is tested to run using nginx and php-fpm as a vhost on a separate server.
Note that you should **never** run FSyncMS without TLS/HTTPS, as login data is passed in Basic Auth form, which is
essentially cleartext. This is not discussed here, but should not be forgotten.

Site config for the local server:

```nginx
server {
	listen *:8080 default_server;
	server_name localhost;
	server_tokens off;
	root /path/to/FSyncMS/public;

	client_max_body_size 20m;

	access_log  /var/log/nginx/ffsync_access.log;
	error_log   /var/log/nginx/ffsync_error.log;

	index index.html index.htm index.php;

	location / {
		try_files $uri $uri/ =404;
	}

	location ~ [^/]\.php(/|$) {
		include snippets/fastcgi-php.conf;

		fastcgi_pass unix:/var/run/php5-fpm.sock;
	}
}
```

From the main configuration for this hostname, proxy requests from the FSyncMS installation directory to this vhost:

```nginx
	location ^~ /sync/ {
		proxy_set_header Host $host;
		proxy_pass http://localhost:8080/;
	}
```

If FSyncMS is to be installed on its own server with no other services in other path prefixes present, adjusting the
first template to listen for the correct server_name is sufficient.