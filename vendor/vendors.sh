#!/bin/sh

curl -s http://getcomposer.org/installer | php
php composer.phar install --prefer-source

