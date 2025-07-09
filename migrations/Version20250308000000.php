<?php

declare( strict_types=1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250308000000 extends AbstractMigration {

	public function getDescription(): string {
		return 'Add books_stored table.';
	}

	public function up( Schema $schema ): void {
		$this->addSql( 'CREATE TABLE IF NOT EXISTS books_stored (
			lang VARCHAR(10) NOT NULL,
			title VARCHAR(255) NOT NULL,
			format VARCHAR(10) NOT NULL,
			images INT(1) NOT NULL,
			credits INT(1) NOT NULL,
			font VARCHAR(100) NOT NULL,
			UNIQUE KEY book ( lang, title, format, images, credits, font ),
			generated_time DATETIME NULL DEFAULT NULL,
			start_time DATETIME NULL DEFAULT NULL,
			last_accessed DATETIME NULL DEFAULT NULL
		) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB' );
	}

	public function down( Schema $schema ): void {
		$this->addSql( 'DROP TABLE books_stored' );
	}
}
