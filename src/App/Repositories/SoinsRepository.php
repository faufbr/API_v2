<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

class SoinsRepository 
{
    public function __construct(private Database $database)
    {

    }
    public function getAll(): array 
    {
        $pdo = $this->database->getConnection();

        $req = $pdo->query('SELECT * FROM soins');

        return $req->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|bool {
        $sql = 'SELECT * FROM soins WHERE id = :id';

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        $req->bindValue(':id', $id, PDO::PARAM_INT);

        $req->execute();

        return $req->fetch(PDO::FETCH_ASSOC);
    }
}