# API_v2

#
## Install httpie (for light tests)
python -m pip install --upgrade pip wheel
python -m pip install httpie

## Install php DI
composer require php-di/php-di

## Run app
php -S localhost:8080 C:\wamp64\www\API_v2\public\index.php

## Run httpie
http get(put, delete...) http://localhost:8080/(table, paramètres...)

#
## How to use this API
Sur Postman, entrez *http://localhost:8080/login* avec la méthode *GET*.
Entrez dans l'onglet *Body* : *{ "login" : "votre_login"}* puis appuyez sur *Send*.
Récupérez votre token, et dans l'onglet *Headers* entrez *Authorization* dans la section *Key* et *Bearer votre_token* dans la section *Value*.
Entrez ensuite la route de votre choix avec la méthode de votre choix et envoyez la requête. (vous avez 30sec)