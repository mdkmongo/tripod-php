apt-get update
apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 7F0CEB10
echo 'deb http://downloads-distro.mongodb.org/repo/ubuntu-upstart dist 10gen' | tee /etc/apt/sources.list.d/mongodb.list
sudo apt-get update
sudo apt-get install -y mongodb-org
apt-get install php5 php-pear
pecl install mongo
apt-get install git siege
git clone https://github.com/talis/tripod-php.git /opt/tripod-php
cd /opt/tripod-php
git checkout performance-test-rest-interface
ln -s /opt/tripod-php/test/performance/rest-interface /var/www/
cd /opt/tripod-php/test/performance/rest-interface/
curl -sS https://getcomposer.org/installer | php
php composer.phar install
a2enmod rewrite
touch /etc/php5/apache2/conf.d/mongo.ini
echo "extension=mongo.so" > /etc/php5/apache2/conf.d/mongo.ini
sed -i "s/AllowOverride None/AllowOverride All/" /etc/apache2/sites-enabled/000-default
service apache2 restart