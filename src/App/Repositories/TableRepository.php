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
        $sql = 'SELECT * FROM ' . $table . ' WHERE id = :id';

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        $req->bindValue(':id', $id, PDO::PARAM_INT);

        $req->execute();

        return $req->fetch(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $params, $table)
    {
        $sql = 'UPDATE ' . $table . ' SET ' . $param . ' = :' . $param . ' WHERE ' . $id . ' = :id';

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);
    }
}