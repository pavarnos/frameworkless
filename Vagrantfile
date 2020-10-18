# -*- mode: ruby -*-
# vi: set ft=ruby :

# see https://github.com/weaveworks/guides/blob/master/nginx-ubuntu-simple/Vagrantfile
# for some nifty tricks with copying files

# if you have trouble with guest additions
# vagrant plugin install vagrant-vbguest

Vagrant.configure(2) do |config|
    config.vm.box = "ubuntu/bionic64"
    config.vm.box_check_update = true

    # add memory for unit tests and profiling
    config.vm.provider "virtualbox" do |v|
      v.memory = 4096
      v.cpus = 3
      # v.linked_clone = true
      v.name = "frameworkless"
    end

    # Create a private network, which allows host-only access to the machine using a specific IP.
    config.vm.network "private_network", ip: "192.168.33.17"
    config.vm.hostname = "frameworkless.local"
    # config.vm.network "forwarded_port", guest: 80, host: 80
    # config.vm.network "forwarded_port", guest: 3306, host: 3306
    # allow mysql: see etc/mysql/conf.d for more instructions
    # ssl problem with chrome? https://scotthelme.co.uk/bypassing-hsts-or-hpkp-in-chrome-is-a-badidea/
    # try typing 'thisisunsafe' when viewing the HSTS error page

    # source code and config for the web site
    # http://jeremykendall.net/2013/08/09/vagrant-synced-folders-permissions/
    # needs to be www-data:www-data so apache can write to var and cache directories, needs to be 777 so phpStorm command line can write to cache directories. Dumb.
    config.vm.synced_folder ".", "/var/www/frameworkless", owner:"www-data", group:"www-data", create: true, mount_options:["dmode=777,fmode=777"]

    # Enable provisioning with a shell script.
    config.vm.provision "shell", inline: <<-SHELL
        export DEBIAN_FRONTEND="noninteractive"
        # php7 support
        sudo add-apt-repository ppa:ondrej/php
        sudo add-apt-repository ppa:ondrej/apache2
        sudo apt update
        sudo apt upgrade -y

        # utility
        # sudo apt-get install -y git geoip-database ntpdate language-pack-en unzip

        # mysql
        sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password rootpassword'
        sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password rootpassword'
        sudo debconf-set-selections <<< "mysql-community-server mysql-community-server/root-pass password rootpassword"
        sudo debconf-set-selections <<< "mysql-community-server mysql-community-server/re-root-pass password rootpassword"
        sudo apt-get install -q -y mysql-server

        # l2s specific mysql configuration / tuning
        if [ -f /etc/mysql/conf.d/frameworkless.cnf ];
        then
            sudo rm /etc/mysql/conf.d/frameworkless.cnf
        fi
        sudo cp /var/www/frameworkless/etc/mysql/conf.d/frameworkless.cnf /etc/mysql/conf.d/frameworkless.cnf
        sudo chmod 644 /etc/mysql/conf.d/frameworkless.cnf
        sudo service mysql restart

        # read mysql user name and passwords out of the config file
        db_name=frameworkless
        db_user=frameworkless
        db_pass=frameworkless
        if [ ! -f /var/log/databasesetup ];
        then
            # add the l2s user to mysql
            echo "CREATE USER '${db_user}'@'%' IDENTIFIED BY '${db_pass}'" | mysql -uroot -prootpassword
            echo "CREATE DATABASE if not exists ${db_name}" | mysql -uroot -prootpassword
            echo "GRANT ALL ON ${db_name}.* TO '${db_user}'@'%'" | mysql -uroot -prootpassword
            echo "flush privileges" | mysql -uroot -prootpassword

            # harden mysql: queries lifted from from /usr/bin/mysql_secure_installation
            echo "DELETE FROM mysql.user WHERE User=''" | mysql -uroot -prootpassword
            echo "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.1.0.1', '::1')" | mysql -uroot -prootpassword
            echo "DROP DATABASE if exists test" | mysql -uroot -prootpassword
            echo "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%'" | mysql -uroot -prootpassword
            echo "flush privileges " | mysql -uroot -prootpassword

            echo "ALTER USER root@localhost IDENTIFIED WITH mysql_native_password BY 'rootpassword'" | mysql -uroot -prootpassword

            touch /var/log/databasesetup
        fi

        # php stuff
        sudo apt-get install -y php7.4 php7.4-fpm php7.4-cli php7.4-apc php7.4-gd php7.4-mysql php7.4-zip php7.4-opcache php7.4-json php7.4-mbstring php7.4-curl php7.4-xml php-common php7.4-common
        sudo phpenmod curl dom

        # debug / development tools
        sudo apt-get install -y php7.4-xdebug

        sudo apt install -y apache2
        sudo a2dismod -q -f autoindex
        sudo a2dismod status mpm_prefork access_compat
        sudo a2enmod ssl rewrite expires mpm_event headers alias fastcgi proxy_fcgi http2 setenvif
        sudo a2dissite 000-default
        sudo a2enconf php7.4-fpm

        # configure php for local settings : cli apache2 fpm
        for serverModule in cli fpm
        do
            destination=/etc/php/7.4/${serverModule}/conf.d/
            for configFile in "98-production.ini" "99-development.ini"
            do
                if [ -s ${destination}${configFile} ];
                then
                    sudo rm ${destination}${configFile}
                fi
                sudo ln -s /var/www/frameworkless/etc/php/mods-available/${configFile} ${destination}${configFile}
            done
        done

        sudo ln -s /var/www/frameworkless/etc/apache2/sites-enabled/frameworkless.conf /etc/apache2/sites-enabled/frameworkless.conf

        # for phpstorm to be able to write its debug files
        sudo mkdir /home/ubuntu/.phpstorm_helpers
        sudo chmod a+rw /home/ubuntu/.phpstorm_helpers
        sudo chown ubuntu:ubuntu /home/ubuntu/.phpstorm_helpers

        # composer
        echo "Installing composer..."
        if [ ! -f /usr/local/bin/composer ]; then
            sudo curl -sS https://getcomposer.org/installer | php
            sudo mv composer.phar /usr/local/bin/composer
        fi
    SHELL

    config.vm.provision "shell", run: "always", inline: <<-SHELL
        # apache seems to not like the self signed certificate immediately after boot, so restart it
        sudo apache2ctl stop
        sudo apache2ctl start

        # something weird about php fpm not recognising our symlinked .ini files, so restart fpm to fix it
        sudo service php7.4-fpm stop
        sudo service php7.4-fpm start
    SHELL
end
