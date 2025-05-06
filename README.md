# Bienvenue sur la page de mon projet d'API (v2)

Cette API répond au besoin du cabinet d'infirmières Kaliémie. Elle permet aux infirmières de gérer leurs visites et aux patients de les consulter. 

J'ai réalisé cette API à l'aide du framework [Slim API](https://www.slimframework.com/) en PHP.

Ce projet nécessite d'installer [Composer](https://getcomposer.org/download/). 
Si vous avez déjà installé les dépendances, rendez-vous à la section du [démarrage du projet](#comment-utiliser-cette-api-).

### Installer Slim

`composer require slim/slim:3.*`

### Installer php DI

`composer require php-di/php-di`

### Démarrer le projet

`php -S localhost:8080 C:\wamp64\www\API_v2\public\index.php`

### Installer httpie (premiers tests/tests légers)

Si vous voulez effectuer quelques premiers tests sans Postman, vous pouvez utiliser httpie :

`python -m pip install --upgrade pip wheel`
`python -m pip install httpie`

### Utiliser httpie

`http get(put, delete...) http://localhost:8080/(table, paramètres...)`

## Comment utiliser cette API ?

Sur Postman, entrez *http://localhost:8080/login* avec la méthode *GET*.

Entrez dans l'onglet *Body* : *{ "login" : "votre_login", "mp": "votre_mp" }* puis appuyez sur *Send*.

Récupérez votre token, et dans l'onglet *Headers* entrez *Authorization* dans la section *Key* et *Bearer votre_token* dans la section *Value*.

Entrez ensuite la route de votre choix avec la méthode de votre choix et envoyez la requête (vous avez **1 minute**).