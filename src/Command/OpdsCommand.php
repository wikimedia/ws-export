<?php

namespace App\Command;

use App\BookProvider;
use App\OpdsBuilder;
use App\Util\Api;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OpdsCommand extends Command {

	protected static $defaultName = 'app:opds';

	/** @var Api */
	private $api;

	public function __construct( Api $api ) {
		parent::__construct();
		$this->api = $api;
	}

	protected function configure() {
		$this->setDescription( 'Generate an OPDS file.' )
			->addOption( 'lang', 'l', InputOption::VALUE_REQUIRED, 'Wikisource language code.' )
			->addOption( 'category', 'c', InputOption::VALUE_REQUIRED, 'Category name to export.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		$io = new SymfonyStyle( $input, $output );

		if ( !$input->getOption( 'lang' ) ) {
			$io->warning( 'Please provide a language code with the --lang option.' );
			return Command::FAILURE;
		}

		if ( !$input->getOption( 'category' ) ) {
			$io->warning( 'Please provide a category with the --category option.' );
			return Command::FAILURE;
		}

		$lang = $input->getOption( 'lang' );
		$category = str_replace( ' ', '_', $input->getOption( 'category' ) );

		date_default_timezone_set( 'UTC' );
		$this->api->setLang( $lang );
		$provider = new BookProvider( $this->api, [ 'categories' => false, 'images' => false ] );

		$exportPath = 'https://wsexport.wmflabs.org/';
		$atomGenerator = new OpdsBuilder( $provider, $this->api, $lang, $exportPath );
		$outputFile = dirname( __DIR__, 2 ) . "/public/opds/$lang/$category.xml";
		if ( !is_dir( dirname( $outputFile ) ) ) {
			mkdir( dirname( $outputFile ), 0755, true );
		}
		file_put_contents( $outputFile, $atomGenerator->buildFromCategory( 'Category:' . $category ) );

		$io->success( "The OPDS file has been created: $outputFile" );
		return Command::SUCCESS;
	}
}
