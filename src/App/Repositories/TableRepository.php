<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

class TableRepository 
{
    public function __construct(private Database $database)
    {

    }
    public function getAll($table): array 
    {
        $pdo = $this->database->getConnection();

        $req = $pdo->query('SELECT * FROM ' . $table);

        return $req->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id, $table): array|bool 
    {
        $sql = "SELECT * FROM $table WHERE id = :id";

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        $req->bindValue(':id', $id, PDO::PARAM_INT);

        $req->execute();

        return $req->fetch(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $params, $table): bool
    {
        $array = [];

        foreach ($params as $key => $value) {
            $array[] = "$key = :$key";
        }

        $sql = "UPDATE $table SET " . implode(', ', $array) . " WHERE id = :id";

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $req->bindValue(":$key", $value);
        }

        $req->bindValue(':id', $id, PDO::PARAM_INT);

        return $req->execute();
    }

    public function delete(int $id, $table): bool
    {
        $sql = "DELETE FROM $table WHERE id = :id";

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        $req->bindValue(':id', $id, PDO::PARAM_INT);

        //Retourne true si au moins une ligne a été supprimée
        return $req->execute() && $req->rowCount() > 0;
    }

    public function create(array $params, $table): int
    {
        $colonnes = array_keys($params);
        $nomcolonnes = array_map(fn($key) => ":$key", $colonnes);

        $sql = "INSERT INTO $table (" . implode(', ', $colonnes) . ") VALUES (" . implode(', ', $nomcolonnes) . ")";

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $req->bindValue(":$key", $value);
        }

        error_log("SQL: " . $sql);
        error_log("Params: " . print_r($params, true));


        if ($req->execute()) {
            //Id du dernier insert créé
            $vretour = (int)$pdo->lastInsertId();
        }
        else {
            $vretour = 0;
        }

        return $vretour;
    }

    public function login(array $params) 
    {
        $login = $params['login'];
        $password = $params['mp'];

        $sql = "SELECT * FROM personne_login WHERE login = :login";

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        $req->bindValue(':login', $login, PDO::PARAM_STR);

        $req->execute();

        $user = $req->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $vretour = ['error' => 'Utilisateur ou mot de passe incorrect'];
        }
        else {
            if (md5($password) === $user['mp']) {
                unset($user['mp']);

                $sql = "UPDATE personne_login SET derniere_connexion = NOW() WHERE id = :id";

                $pdo = $this->database->getConnection();

                $req = $pdo->prepare($sql);

                $req->bindValue(':id', $user['id'], PDO::PARAM_INT);

                $req->execute();
                
                $vretour = $user;
            }
            else {
                $vretour = ['error' => 'Utilisateur ou mot de passe incorrect'];
            }
        }

        return $vretour;
    }
}