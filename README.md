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
