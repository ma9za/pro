<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            // التأكد من وجود مجلد database
            $dbDir = dirname(DB_PATH);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            // الاتصال بـ SQLite
            $this->conn = new PDO(
                "sqlite:" . DB_PATH,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // تفعيل Foreign Keys في SQLite
            $this->conn->exec("PRAGMA foreign_keys = ON");
        } catch(PDOException $e) {
            die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    // منع النسخ
    private function __clone() {}

    // منع إلغاء التسلسل
    public function __wakeup() {
        throw new Exception("لا يمكن إلغاء تسلسل Singleton");
    }
}
