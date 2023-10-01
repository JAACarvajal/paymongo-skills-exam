# Paymongo Skills Exam

## Pre-requisites

-   [PHP 8.1.24](https://windows.php.net/download#php-8.1)
-   [Composer 2.5.8](https://getcomposer.org/download/)

## Installation

-   ### Windows

    `Pre-requisites are required.`

    1. Clone repository [link](https://github.com/JAACarvajal/paymongo-skills-exam) and go to the directory
    2. Install dependencies
        ```
        composer install --ignore-platform-req=ext-fileinfo
        ```
    3. Create `.env` file (copy contents from `.env.example`)
    4. Start server
        ```
        php artisan serve
        ```
    5. Test server in browser http://localhost:8000/ -> localhost will be used for windows

    `NOTE: In postman, {{local_url}} value can be changed to http://localhost:8000/api (default value is api-local.parkingsystem.com)`

-   ### WSL (Ubuntu)

    1. Install WSL Ubuntu - [follow this guide](https://ubuntu.com/tutorials/install-ubuntu-on-wsl2-on-windows-11-with-gui-support#1-overview)
        ```
        // update packages
        sudo apt update
        ```
    2. Remove Apache and Install NGINX
        ```
        sudo service apache2 stop
        sudo apt-get purge apache2 apache2-utils apache2.2-bin apache2-common
        sudo apt-get purge apache2 apache2-utils apache2-bin apache2.2-common
        sudo apt-get autoremove
        whereis apache2
        sudo rm -rf /etc/apache2
        ```
        ```
        sudo apt install nginx
        sudo service nginx restart
        sudo service nginx status
        ```
    3. Install PHP
        ```
        sudo apt install php8.1 php8.1-bcmath php8.1-bz2 php8.1-cgi php8.1-cli php8.1-common php8.1-curl php8.1-dba php8.1-dev php8.1-enchant php8.1-fpm php8.1-gd php8.1-gmp php8.1-imap php8.1-interbase php8.1-intl php8.1-ldap php8.1-mbstring php8.1-mysql php8.1-odbc php8.1-opcache php8.1-pgsql php8.1-phpdbg php8.1-pspell php8.1-readline php8.1-snmp php8.1-soap php8.1-sqlite3 php8.1-sybase php8.1-tidy php8.1-xml php8.1-xmlrpc php8.1-xsl php8.1-zip php8.1-gd php8.1-memcached
        ```
        ```
        sudo service php8.1-fpm restart
        sudo service php8.1-fpm status
        php -v
        ```
    4. Install Memcached
        ```
        sudo apt install memcached
        sudo service memcached restart
        sudo service memcached status
        ```
    5. Install Composer
        ```
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php -r "if (hash_file('sha384', 'composer-setup.php') === 'e21205b207c3ff031906575712edab6f13eb0b361f2085f1f1237b7126d785e826a450292b6cfd1d64d92e6563bbde02') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
        php composer-setup.php
        php -r "unlink('composer-setup.php');"
        sudo mv composer.phar /usr/local/bin/composer
        composer -v
        ```
    6. Clone Clone repository [link](https://github.com/JAACarvajal/paymongo-skills-exam)
        ```
        cd /var/www
        git clone {repo}
        ```
    7. Change ownership of the project
        ```
        sudo chmod -R 777 .
        sudo chown -R username:www-data /var/www/paymongo-skills-exam
        sudo git config --global --add safe.directory /var/www/paymongo-skills-exam
        sudo service nginx restart
        ```
    8. Install dependencies
        ```
        sudo composer install
        ```
    9. Install VS code for linux
        ```
        sudo snap install --classic code
        code . // open code
        ```
    10. Create `.env` file (copy contents from `.env.example`)
    11. Add host

        ```
        cd /etc/nginx/sites-available
        sudo nano api-local.parkingsystem.com
        ```

        ```
        // Paste this
        server {
            listen 80;
            server_name api-local.parkingsystem.com;

            root /var/www/paymongo-skills-exam/public;
            index index.htm index.html index.php;

            location / {
                try_files $uri $uri/ /index.php?$query_string;
            }

            location ~ \.php$ {
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/run/php/php8.1-fpm.sock;
            }

            location ~ /\.ht {
                deny all;
            }
        }

        NOTE: Ctrl-O, Enter, Ctrl-X to save file
        ```

        ```
        sudo ln -s /etc/nginx/sites-available/api-local.parkingsystem.com /etc/nginx/sites-enabled
        sudo service nginx restart
        sudo service nginx status
        ```

    12. Add 127.0.0.1 api-local.parkingsystem.com to hosts file

    `NOTE: If you have issues on memcached, change the CACHE_DRIVER value in .env to file.`

## Testing

-   [Postman](https://www.postman.com/downloads/)
-   Import the collection located (paymongo_skills_exam.postman_collection) in the repository and follow the provided steps found in the `Paymongo` directory on how to run each scenario.
