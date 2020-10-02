<?php

namespace App\Command;

use App\CreationLog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command {

	protected static $defaultName = 'app:install';

	/** @var CreationLog */
	private $creationLog;

	public function __construct( CreationLog $creationLog ) {
		parent::__construct();
		$this->creationLog = $creationLog;
	}

	protected function configure() {
		$this->setDescription( 'Install the application.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		$this->creationLog->createTable();
		return Command::SUCCESS;
	}
}
