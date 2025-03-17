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

    public function create(array $params, $table)
    {
        $colonnes = array_keys($params);
        $array = array_map(fn($key) => ":$key", $colonnes);

        foreach ($params as $key => $value) {
            $array[] = "$key = :$key";
        }

        $sql = "INSERT INTO $table (" . implode(', ', $colonnes) . ") VALUES (" . implode(', ', $array) . ")";

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $req->bindValue(":$key", $value);
        }

        if ($req->execute()) {
            //Id du dernier insert créé
            $vretour = (int)$pdo->lastInsertId();
        }
        else {
            $vretour = 0;
        }

        return $vretour;
    }
}