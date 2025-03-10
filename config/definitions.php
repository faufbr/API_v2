<?php

use App\Database;

return [

    Database::class => function() {

        return new Database(host: 'localhost', name: 'ap4_faustine', user: 'root', password: '');
    }
];