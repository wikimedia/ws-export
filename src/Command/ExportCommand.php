<?php

namespace App\Command;

use App\BookCreator;
use App\FontProvider;
use App\GeneratorSelector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExportCommand extends Command {

	/** @var string */
	protected static $defaultName = 'app:export';

	/** @var FontProvider */
	private $fontProvider;

	/** @var BookCreator */
	private $bookCreator;

	public function __construct( FontProvider $fontProvider, BookCreator $bookCreator ) {
		parent::__construct();
		$this->fontProvider = $fontProvider;
		$this->bookCreator = $bookCreator;
	}

	protected function configure() {
		$formatDesc = 'Export format. One of: ' . implode( ', ', array_keys( GeneratorSelector::$formats ) );
		$this->setDescription( 'Export a book.' )
			->addOption( 'lang', 'l', InputOption::VALUE_REQUIRED, 'Wikisource language code.' )
			->addOption( 'title', 't', InputOption::VALUE_REQUIRED, 'Wiki page name of the work to export. Required' )
			->addOption( 'format', 'f', InputOption::VALUE_REQUIRED, $formatDesc, 'epub-3' )
			->addOption( 'path', 'p', InputOption::VALUE_REQUIRED, 'Filesystem path to export to.', dirname( __DIR__, 2 ) )
			->addOption( 'nocredits', null, InputOption::VALUE_NONE, 'Do not include the credits list in the exported ebook.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		$io = new SymfonyStyle( $input, $output );

		if ( !$input->getOption( 'title' ) ) {
			$io->warning( 'Please provide a title with the --title option.' );
			return Command::FAILURE;
		}

		if ( !$input->getOption( 'lang' ) ) {
			$io->warning( 'Please provide a language code with the --lang option.' );
			return Command::FAILURE;
		}

		$input->validate();

		$this->bookCreator->setTitle( $input->getOption( 'title' ) );
		$this->bookCreator->setLang( $input->getOption( 'lang' ) );
		$this->bookCreator->setOutputDir( $input->getOption( 'path' ) );
		$this->bookCreator->setFormat( $input->getOption( 'format' ) );
		$this->bookCreator->setIncludeCredits( !$input->getOption( 'nocredits' ) );

		$this->bookCreator->create();

		$io->success( "The ebook has been created: " . $this->bookCreator->getFilePath() );

		return Command::SUCCESS;
	}
}
