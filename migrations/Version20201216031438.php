<?php

declare( strict_types=1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add a ID primary key to books_generated.
 */
final class Version20201216031438 extends AbstractMigration {
	public function getDescription() : string {
		return 'Add primary key column to books_generated';
	}

	/**
	 * Add the id column.
	 * @param Schema $schema
	 */
	public function up( Schema $schema ) : void {
		$this->addSql( 'ALTER TABLE books_generated ADD id INT AUTO_INCREMENT NOT NULL FIRST, ADD PRIMARY KEY (id)' );
	}

	/**
	 * Remove the id column.
	 * @param Schema $schema
	 */
	public function down( Schema $schema ) : void {
		$this->addSql( 'ALTER TABLE books_generated MODIFY id INT NOT NULL' );
		$this->addSql( 'ALTER TABLE books_generated DROP PRIMARY KEY' );
		$this->addSql( 'ALTER TABLE books_generated DROP id' );
	}
}
