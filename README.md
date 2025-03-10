# API_v2

#
# Install httpie
python -m pip install --upgrade pip wheel
python -m pip install httpie

# Install php DI
composer require php-di/php-di

# Run app
php -S localhost:8080 C:\wamp64\www\API_v2\public\index.php

# Run httpie
http get http://localhost:8080/