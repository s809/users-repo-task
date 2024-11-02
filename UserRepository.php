<?php

class UserRepository {
    /** @var \mysqli */
    private $mysqli;

    /** @var \mysqli_stmt */
    private $createStmt;
    /** @var \mysqli_stmt */
    private $getAllStmt;
    /** @var \mysqli_stmt */
    private $getByIdStmt;
    /** @var \mysqli_stmt */
    private $deleteByIdStmt;
    /** @var \mysqli_stmt */
    private $deleteAllStmt;

    public function __construct() {
        try {
            $this->mysqli = mysqli_init();
            $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, Config::CONNECT_TIMEOUT);
            $this->mysqli->real_connect(...Config::DB_PARAMS);

            $this->createStmt = $this->mysqli->prepare("INSERT INTO users(full_name, role, efficiency) VALUES (?, ?, ?)");
            $this->getAllStmt = $this->mysqli->prepare("SELECT * FROM users");
            $this->getByIdStmt = $this->mysqli->prepare("SELECT * FROM users WHERE id = ?");
            $this->deleteByIdStmt = $this->mysqli->prepare("DELETE FROM users WHERE id = ?");
            $this->deleteAllStmt = $this->mysqli->prepare("DELETE FROM users");
        } catch (\Exception $e) {
            $this->mysqli = null;
        }
    }



    public function test() {
        if (!$this->mysqli)
            return "test";

        return $this->mysqli->host_info;
    }

    private function getTestUser()
    {
        return [
            "id" => 1,
            "full_name" => "Full Name",
            "role" => "Role",
            "efficiency" => 10
        ];
    }


    public function create(array $data) {
        if (!$this->mysqli)
            return 1;

        $this->createStmt->bind_param("ssi", $data["full_name"], $data["role"], $data["efficiency"]);
        $this->createStmt->execute();

        return $this->mysqli->insert_id;
    }

    public function getById(int $id) {
        if (!$this->mysqli) {
            return rand() > getrandmax() / 2
                ? $this->getTestUser()
                : null;
        }

        $this->getByIdStmt->bind_param("i", $id);
        $this->getByIdStmt->execute();
        $result = $this->getByIdStmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC)[0];
    }

    public function getByCriteria(array $params) {
        if (!$this->mysqli) {
            return rand() > getrandmax() / 2
                ? [$this->getTestUser(), $this->getTestUser(), $this->getTestUser()]
                : [];
        }

        if (!empty($params)) {
            $stmt = $this->mysqli->prepare(
                "SELECT * FROM users WHERE " .
                implode(" AND ", array_map(fn($key) => "$key = ?", array_keys($params)))
            );

            $paramValues = array_values($params);
            $stmt->bind_param(str_repeat("s", count($params)), ...$paramValues);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $this->getAllStmt->execute();
            $result = $this->getAllStmt->get_result();
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function update($id, array $params)
    {
        if (!$this->mysqli) {
            return rand() > getrandmax() / 2
                ? $this->getTestUser()
                : null;
        }

        // В теории здесь можно транзакции
        $stmt = $this->mysqli->prepare(
            "UPDATE users SET " .
            implode(", ", array_map(fn($key) => "$key = ?", array_keys($params))) .
            " WHERE id = ?"
        );

        $paramValues = array_values($params);
        $paramValues[] = $id;
        $stmt->bind_param(str_repeat("s", count($params)) . "i", ...$paramValues);
        $stmt->execute();

        $this->getByIdStmt->bind_param("i", $id);
        $this->getByIdStmt->execute();
        $result = $this->getByIdStmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC)[0];
    }

    public function deleteById($id) {
        if (!$this->mysqli) {
            return rand() > getrandmax() / 2
                ? $this->getTestUser()
                : null;
        }

        // В теории здесь можно транзакции
        $this->getByIdStmt->bind_param("i", $id);
        $this->getByIdStmt->execute();
        $user = $this->getByIdStmt->get_result()->fetch_all(MYSQLI_ASSOC)[0];
        if (!$user) {
            return null;
        }

        $this->deleteByIdStmt->bind_param("i", $id);
        $this->deleteByIdStmt->execute();

        return $user;
    }

    public function deleteAll() {
        if (!$this->mysqli) {
            return;
        }

        $this->deleteAllStmt->execute();
    }

}