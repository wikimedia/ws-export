<?php

declare( strict_types=1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Intitial migartion to create the table.
 */
final class Version20201216012133 extends AbstractMigration {
	public function getDescription() : string {
		return 'Create the books_generated table';
	}

	/**
	 * Creates the table.
	 * @param Schema $schema
	 */
	public function up( Schema $schema ) : void {
		$this->addSql( 'CREATE TABLE IF NOT EXISTS books_generated (time DATETIME NOT NULL, lang VARCHAR(10) NOT NULL, title VARCHAR(255) NOT NULL, format VARCHAR(10) NOT NULL, INDEX time (time), INDEX lang (lang)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB' );
	}

	/**
	 * Drops the table.
	 * @param Schema $schema
	 */
	public function down( Schema $schema ) : void {
		$this->addSql( 'DROP TABLE books_generated' );
	}
}
