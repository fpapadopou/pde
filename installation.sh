#!/bin/bash

# Installation script for Parser Development Environment (PDE) for local development
# The script installs all necessary dependencies for the application and then installs PDE.
# First, run "vagrant up", then ssh to the new VM and run the scirpt.
# Runs on Ubuntu Xenial 16.04 and user input is necessary throughout the process.

# Exit on error
set -e
# Echo commands
set -x

sudo apt-get update
sudo apt-get upgrade -y
# Install LAMP stack as per here: https://www.unixmen.com/how-to-install-lamp-stack-on-ubuntu-16-04
#------------------------------------------------------------------#
sudo apt-get install -y apache2
sudo systemctl enable apache2
sudo systemctl start apache2
sudo systemctl status apache2

#------------------------------------------------------------------#
sudo apt-get install -y mysql-server mysql-client
sudo systemctl status mysql
# Create a 'pde' database which is used later during the PDE installation
echo "Insert the MySQL password you used above in order to create pde database:"
mysql -u root -p -e 'create database pde;'

#------------------------------------------------------------------#
sudo apt-get update 
sudo apt-get install -y php7.0-mysql php7.0-curl php7.0-json php7.0-cgi php7.0-imap php7.0 libapache2-mod-php7.0
php -v
sudo service apache2 restart

#------------------------------------------------------------------#
sudo apt-get install -y phpmyadmin php-mbstring php-gettext
sudo phpenmod mcrypt
sudo phpenmod mbstring
sudo service apache2 restart

# Install Docker Engine and its dependencies and allow current user and apache2 to access Docker
#------------------------------------------------------------------#
sudo apt-get install -y linux-image-extra-$(uname -r) linux-image-extra-virtual
sudo apt-get install -y apt-transport-https ca-certificates curl software-properties-common
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"
sudo apt-get update
sudo apt-get install -y docker-ce

#------------------------------------------------------------------#
sudo gpasswd -a ${USER} docker
sudo gpasswd -a www-data docker
sudo service docker restart

#------------------------------------------------------------------#
sudo docker pull fpapadopou/pde-command-container:latest
sudo docker tag fpapadopou/pde-command-container:latest pde-command-container:latest

# Install PDE
#------------------------------------------------------------------#
sudo mkdir /opt/parser_development_environment
sudo chown ubuntu:ubuntu /opt/parser_development_environment
cd /opt/parser_development_environment
rsync -rvC --update /home/ubuntu/pde_source/ /opt/parser_development_environment/pde
sudo chown ubuntu:ubuntu /opt/parser_development_environment
cd pde/Symfony
cd /opt/parser_development_environment/pde/Symfony
cp app/config/parameters.yml.dist app/config/parameters.yml
curl -s http://getcomposer.org/installer | php
php composer.phar install
php app/console doctrine:database:create
php app/console doctrine:schema:create
php app/console pde:settings:set
# Finish installation, which should fail above due to known bug
php composer.phar install
sudo mkdir /opt/parser_development_environment/file_storage
sudo chown -R ubuntu:www-data /opt/parser_development_environment/
sudo chmod -R 775 /opt/parser_development_environment/

# Enable site in apache server
#------------------------------------------------------------------#
cd ..
sudo cp pde-apache.conf /etc/apache2/sites-available/pde.conf
sudo a2dissite 000-default.conf
sudo a2ensite pde.conf
sudo a2enmod rewrite
sudo service apache2 restart

echo "Installation completed"
echo "You need to run \"newgrp docker\" before using the app"
