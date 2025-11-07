<?php
class LoginData {
    private $pdo;

    public function __construct() {
        $this->pdo = new PDO('mysql:host=localhost;dbname=test;charset=utf8', 'user', 'pass');
    }

    public function getUserByUsername($username) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
