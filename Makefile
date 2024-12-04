deploy: deploy-app deploy-nginx deploy-mysql deploy-phpini

deploy-app:
	rsync -av webapp/php/ isu01:~/private_isu/webapp/php/

deploy-nginx:
	rsync -av --rsync-path="sudo rsync" ./etc/nginx/nginx.conf isu01:/etc/nginx/nginx.conf
	rsync -av --rsync-path="sudo rsync" ./etc/nginx/sites-available/isucon-php.conf isu01:/etc/nginx/sites-available/isucon-php.conf
	ssh isu01 "sudo systemctl restart nginx"

deploy-mysql:
	rsync -av --rsync-path="sudo rsync" ./etc/mysql/mysql.conf.d/mysqld.cnf isu01:/etc/mysql/mysql.conf.d/mysqld.cnf
	ssh isu01 "sudo systemctl restart mysql"

deploy-phpini83:
	rsync -av --rsync-path="sudo rsync" ./etc/php/8.3/mods-available/opcache.ini isu01:/etc/php/8.3/mods-available/opcache.ini
	rsync -av --rsync-path="sudo rsync" ./etc/php/8.3/fpm/pool.d/www.conf isu01:/etc/php/8.3/fpm/pool.d/www.conf
	ssh isu01 "sudo systemctl restart php8.3-fpm"

deploy-phpini84:
	rsync -av --rsync-path="sudo rsync" ./etc/php/8.4/mods-available/opcache.ini isu01:/etc/php/8.4/mods-available/opcache.ini
	rsync -av --rsync-path="sudo rsync" ./etc/php/8.4/fpm/pool.d/www.conf isu01:/etc/php/8.4/fpm/pool.d/www.conf
	ssh isu01 "sudo systemctl restart php8.4-fpm"

# GitHubに貼りやすいようについでにフォーマットをMarkdownにしつつ、pbcopyでクリップボードにコピーする
analyze-nginx:
	ssh isu01 "sudo cat /var/log/nginx/access.log | alp json -m '^/@.+,^/posts/\d+,^/image/.+' --sort sum -r --format markdown" | pbcopy

# pt-query-digestの結果が長いのでファイルに書き出す
analyze-mysql:
	ssh isu01 "sudo cat /var/log/mysql/mysql-slow.log | pt-query-digest --limit 10" > slow_query.log

flamegraph:
	ssh isu01 "sudo ~/reli-prof/reli c:flamegraph < ~/reli-prof/traces.log > ~/reli-prof/traces.svg"
	rsync isu01:~/reli-prof/traces.svg ./
	open traces.svg

truncate-logs:
	ssh isu01 "sudo truncate -s 0 /var/log/nginx/access.log"
	ssh isu01 "sudo truncate -s 0 /var/log/mysql/mysql-slow.log"
	ssh isu01 "rm ~/reli-prof/traces.log"
