base on Yaf3.06，集成ORM，性能卓越，易于扩展
==
phpStudy nginx rewrite(win7 php5.6)
=
```bash
location ~ \.php {
	fastcgi_pass 127.0.0.1:9000;
	fastcgi_split_path_info ^(.+\.php)(/.+)$;
	fastcgi_index /index.php;
	fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
	include fastcgi_params;
}

if (!-e $request_filename) {
	rewrite ^/(.*\.(js|ico|gif|jpg|png|css|bmp|html|xls)$) /public/$1 last;
	rewrite ^/(.*) /index.php/$1 last;
}
```

nginx rewrite(ubuntu16.04.1 php7.1)
=
```bash
	location / {
                # First attempt to serve request as file, then
                #e as directory, then fall back to displaying a 404.
                #index  index.html index.htm index.php;
                #if (!-e $request_filename){
                #       rewrite ^/(.*)$ /index.php/$1 last;
                #}
                #try_files $uri $uri/ =404;
                #try_files $uri $uri/ /index.php;
                index index.html index.htm index.php;
               if (-e $request_filename) {
                       break;
               }
               if (!-e $request_filename) {
                       rewrite ^/(.*)$ /index.php/$1 last;
                       break;
               }
        }

        # pass the PHP scripts to FastCGI server listening on 127.0.0.1:9000

        location ~ .+\.php($|/)  {
                include snippets/fastcgi-php.conf;
                #fastcgi_index index.php;
                ## With php7.0-cgi alone:
                ##fastcgi_pass 127.0.0.1:9000;
                ## With php7.0-fpm:
                #fastcgi_pass unix:/run/php/php7.0-fpm.sock;
                #fastcgi_split_path_info ^(.+\.php)(/.+)$;
                fastcgi_pass unix:/var/run/php/php7.1-fpm.sock;
                #fastcgi_pass unix:/run/php
        }

```
