<?php

declare( strict_types=1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201222094802 extends AbstractMigration {

	public function getDescription(): string {
		return 'Add duration column.';
	}

	public function up( Schema $schema ): void {
		$this->addSql( 'ALTER TABLE books_generated ADD duration INT DEFAULT NULL' );
	}

	public function down( Schema $schema ): void {
		$this->addSql( 'ALTER TABLE books_generated DROP duration' );
	}
}
