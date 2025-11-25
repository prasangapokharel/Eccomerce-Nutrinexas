<?php
namespace App\Commands;

use App\Core\Command;
use App\Core\Database;

class ReviewAlterMigration extends Command
{
    public function execute(array $args = []): int
    {
        $db = Database::getInstance();
        $pdo = $db->getPdo();

        try {
            $this->output('Starting Review table migration...', 'info');
            $pdo->beginTransaction();

            // 1) Drop existing FK on user_id if present
            $fkName = null;
            $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'user_id' AND REFERENCED_TABLE_NAME = 'users'");
            $row = $stmt->fetch();
            if ($row && !empty($row['CONSTRAINT_NAME'])) {
                $fkName = $row['CONSTRAINT_NAME'];
            }
            if ($fkName) {
                $this->output("Dropping foreign key $fkName on reviews.user_id", 'info');
                $pdo->exec("ALTER TABLE reviews DROP FOREIGN KEY `$fkName`");
            }

            // 2) Make user_id nullable
            $this->output('Altering reviews.user_id to NULL', 'info');
            $pdo->exec("ALTER TABLE reviews MODIFY user_id INT(11) NULL");

            // 3) Add media columns if missing
            $this->addColumnIfMissing($pdo, 'reviews', 'image_path', "VARCHAR(500) NULL AFTER `review`");
            $this->addColumnIfMissing($pdo, 'reviews', 'video_path', "VARCHAR(500) NULL AFTER `image_path`");
            $this->addColumnIfMissing($pdo, 'reviews', 'image_file_id', "VARCHAR(100) NULL AFTER `video_path`");
            $this->addColumnIfMissing($pdo, 'reviews', 'video_file_id', "VARCHAR(100) NULL AFTER `image_file_id`");

            // 4) Recreate FK with ON DELETE SET NULL
            $this->output('Creating foreign key on reviews.user_id -> users.id (ON DELETE SET NULL)', 'info');
            $pdo->exec("ALTER TABLE reviews ADD CONSTRAINT fk_reviews_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE");

            $pdo->commit();
            $this->output('Review table migration completed successfully.', 'success');
            return 0;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->output('Migration failed: ' . $e->getMessage(), 'error');
            return 1;
        }
    }

    private function addColumnIfMissing(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        $exists = (int)$stmt->fetchColumn() > 0;
        if (!$exists) {
            $this->output("Adding column $column to $table", 'info');
            $pdo->exec("ALTER TABLE `$table` ADD `$column` $definition");
        }
    }
}


