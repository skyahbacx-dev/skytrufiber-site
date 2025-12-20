<?php
require_once __DIR__ . '/db_connect_mysql.php';
require_once __DIR__ . '/db_connect_neon.php';

class DualDB {
    private PDO $mysql;
    private ?PDO $neon;

    public function __construct($mysql, $neon) {
        $this->mysql = $mysql;
        $this->neon  = $neon;
    }

    public function prepare(string $sql) {
        return new DualStatement($this->mysql, $this->neon, $sql);
    }

    public function lastInsertId() {
        return $this->mysql->lastInsertId();
    }
}

class DualStatement {
    private PDOStatement $stmtMy;
    private ?PDOStatement $stmtPg;
    private string $sql;

    public function __construct(PDO $mysql, ?PDO $neon, string $sql) {
        $this->sql = trim($sql);
        $this->stmtMy = $mysql->prepare($sql);
        $this->stmtPg = $neon ? $neon->prepare($sql) : null;
    }

    public function execute(array $params = []) {
        $this->stmtMy->execute($params);

        // Dual-write ONLY if not SELECT
        if ($this->stmtPg && stripos($this->sql, 'select') !== 0) {
            try {
                $this->stmtPg->execute($params);
            } catch (PDOException $e) {
                error_log("NEON WRITE FAIL: " . $e->getMessage());
            }
        }
        return true;
    }

    public function fetch($mode = PDO::FETCH_ASSOC) {
        return $this->stmtMy->fetch($mode);
    }

    public function fetchAll($mode = PDO::FETCH_ASSOC) {
        return $this->stmtMy->fetchAll($mode);
    }
}

$conn = new DualDB($pdo_mysql, $pdo_neon);
