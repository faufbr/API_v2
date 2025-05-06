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
    public function getAll(string $table, ?int $userId, ?string $userRole): array 
    {
        $pdo = $this->database->getConnection();

        $sql = "SELECT * FROM $table";
        $params = [];

        switch ($userRole) {
            case 'patient':
                switch ($table) {
                    case 'patient':
                        $sql .= " WHERE id = :user_id";
                        $params[':user_id'] = $userId;
                        break;
                    case 'visite':
                        $sql .= " WHERE patient = :user_id";
                        $params[':user_id'] = $userId;
                        break;
                }
                break;
            case 'infirmiere':
                switch ($table) {
                    case 'patient':
                        $sql .= " WHERE infirmiere_souhait = :user_id";
                        $params[':user_id'] = $userId;
                        break;
                    case 'visite':
                        $sql .= " WHERE infirmiere = :user_id";
                        $params[':user_id'] = $userId;
                        break;
                }
                break;
        }

        $req = $pdo->prepare($sql);

        error_log("SQL exécutée : $sql");
        error_log("Paramètres : " . print_r($params, true));


        $req->execute($params);

        return $req->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id, string $table, ?int $userId, ?string $userRole): array|bool 
    {
        $sql = "SELECT * FROM $table WHERE id = :id";

        switch ($userRole) {
            case 'patient':
                switch ($table) {
                    case 'patient':
                        $sql .= " AND id = :user_id";
                        break;
                    case 'visite':
                        $sql .= " AND patient = :user_id";
                        break;
                }
                break;
            case 'infirmiere':
                switch ($table) {
                    case 'patient':
                        $sql .= " AND infirmiere_souhait = :user_id";
                        break;
                    case 'visite':
                        $sql .= " AND infirmiere = :user_id";
                        break;
                }
                break;
        }

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        $req->bindValue(':id', $id, PDO::PARAM_INT);

        if ($userRole) {
            $req->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }

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
            if (md5($password) === $user['mp'] && $user['nb_tentative_erreur'] < 3) {
                unset($user['mp']);

                $sql = "UPDATE personne_login SET derniere_connexion = NOW() WHERE id = :id";

                $pdo = $this->database->getConnection();

                $req = $pdo->prepare($sql);

                $req->bindValue(':id', $user['id'], PDO::PARAM_INT);

                $req->execute();

                $token = $this->createToken($user['id']);
                
                $vretour = ['user' => $user, 'token' => $token];
            }
            else {
                if ($user['nb_tentative_erreur'] >= 3) {
                    $vretour = ['error' => 'Compte bloqué'];
                }
                else {
                    $sql = "UPDATE personne_login SET nb_tentative_erreur = nb_tentative_erreur + 1 WHERE id = :id";

                    $pdo = $this->database->getConnection();

                    $req = $pdo->prepare($sql);

                    $req->bindValue(':id', $user['id'], PDO::PARAM_INT);

                    $req->execute();

                    $vretour = ['error' => 'Utilisateur ou mot de passe incorrect'];
                }
            }
        }

        return $vretour;
    }

    public function createToken(int $idLogin) 
    {
        $role = $this->getRole($idLogin);

        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(['id' => $idLogin, 'function' => $role, 'exp' => (time() + 60)]));
        $signature = base64_encode(hash_hmac('sha256', "$header.$payload", 'ffabre', true));
        $token = "$header.$payload.$signature";

        $sql = "INSERT INTO token (id_login, date, jeton) VALUES (:id_login, NOW(), :token)";

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        $req->bindValue(':id_login', $idLogin, PDO::PARAM_INT);
        $req->bindValue(':token', $token, PDO::PARAM_STR);

        $req->execute();

        return $token;
    }

    public function getRole(int $id)
    {
        // Rôle par défaut pour être + sécurisé
        $role = 'patient';
        if ($this->checkCheffe($id))
        {
            $role = 'infirmiere_cheffe';
        }
        else 
        {
            if ($this->checkInfirmiere($id))
            {
                $role = 'infirmiere';
            }
            elseif ($this->checkAdmin($id))
            {
                $role = 'administrateur';
            }
        }

        return $role;
    }

    public function verifyToken(string $jwt)
    {
        try {
            [$header, $payload, $signature] = explode('.', $jwt);

            $signatureValide = base64_encode(hash_hmac('sha256', "$header.$payload", 'ffabre', true));

            if ($signature !== $signatureValide) {
                $vretour = false;
            }
            else {
                $payloadDechiffre = json_decode(base64_decode($payload), true);
                if ($payloadDechiffre['exp'] < time()) {
                    $vretour = false;
                }
                else {
                    $vretour = $payloadDechiffre;
                }
            }
        }
        catch(\Exception $ex) {
            error_log("Erreur lors de la vérification du token : " . $ex->getMessage());
            $vretour = false;
        }

        error_log("Payload déchiffré : " . print_r($payloadDechiffre, true));
        return $vretour;
    }

    public function checkCheffe(int $id)
    {
        $sql = "SELECT cheffe FROM infirmiere WHERE id = :id";

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        $req->bindValue(':id', $id, PDO::PARAM_INT);

        $req->execute();

        $result = $req->fetch(PDO::FETCH_ASSOC);

        return isset($result['cheffe']) && $result['cheffe'] == 1;
    }

    public function checkInfirmiere(int $id): bool
    {
        $sql = "SELECT id FROM infirmiere WHERE id = :id";

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        $req->bindValue(':id', $id, PDO::PARAM_INT);

        $req->execute();

        return $req->rowCount() > 0;
    }

    public function checkAdmin(int $id): bool
    {
        $sql = "SELECT id FROM administrateur WHERE id = :id";

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);

        $req->bindValue(':id', $id, PDO::PARAM_INT);

        $req->execute();

        return $req->rowCount() > 0;
    }

    public function getUserById(int $id): ?array
    {
        $sql = "SELECT id, nom, prenom FROM personne WHERE id = :id";

        $pdo = $this->database->getConnection();

        $req = $pdo->prepare($sql);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $req->execute();

        $user = $req->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
}