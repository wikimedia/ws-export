<?php

namespace App\Command;

use App\BookProvider;
use App\FileCache;
use App\OpdsBuilder;
use App\Repository\CreditRepository;
use App\Util\Api;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OpdsCommand extends Command {

	/** @var Api */
	private $api;

	/** @var CreditRepository */
	private $creditRepo;

	/** @var FileCache */
	private $fileCache;

	public function __construct( Api $api, CreditRepository $creditRepo, FileCache $fileCache ) {
		parent::__construct( 'app:opds' );
		$this->api = $api;
		$this->creditRepo = $creditRepo;
		$this->fileCache = $fileCache;
	}

	protected function configure(): void {
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
		$provider = new BookProvider( $this->api, $this->creditRepo, $this->fileCache );

		$exportPath = 'https://ws-export.wmcloud.org/';
		$atomGenerator = new OpdsBuilder( $provider, $this->api, $lang, $this->fileCache, $exportPath );
		$outputFile = dirname( __DIR__, 2 ) . "/public/opds/$lang/$category.xml";
		if ( !is_dir( dirname( $outputFile ) ) ) {
			mkdir( dirname( $outputFile ), 0755, true );
		}
		file_put_contents( $outputFile, $atomGenerator->buildFromCategory( 'Category:' . $category ) );

		$io->success( "The OPDS file has been created: $outputFile" );
		return Command::SUCCESS;
	}
}
