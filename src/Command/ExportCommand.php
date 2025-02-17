<?php

namespace App\Command;

use App\BookCreator;
use App\FileCache;
use App\GeneratorSelector;
use App\Repository\CreditRepository;
use App\Util\Api;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExportCommand extends Command {

	/** @var GeneratorSelector */
	private $generatorSelector;

	/** @var CreditRepository */
	private $creditRepo;

	/** @var Api */
	private $api;

	/** @var bool */
	private $enableCache;

	/** @var FileCache */
	private $fileCache;

	public function __construct( GeneratorSelector $generatorSelector, CreditRepository $creditRepo, Api $api, bool $enableCache, FileCache $fileCache ) {
		parent::__construct( 'app:export' );
		$this->generatorSelector = $generatorSelector;
		$this->creditRepo = $creditRepo;
		$this->api = $api;
		$this->enableCache = $enableCache;
		$this->fileCache = $fileCache;
	}

	protected function configure(): void {
		$formatDesc = 'Export format. One of: ' . implode( ', ', array_keys( GeneratorSelector::getValidFormats() ) );
		$this->setDescription( 'Export a book.' )
			->addOption( 'lang', 'l', InputOption::VALUE_REQUIRED, 'Wikisource language code.' )
			->addOption( 'title', 't', InputOption::VALUE_REQUIRED, 'Wiki page name of the work to export. Required' )
			->addOption( 'format', 'f', InputOption::VALUE_REQUIRED, $formatDesc, 'epub-3' )
			->addOption( 'path', 'p', InputOption::VALUE_REQUIRED, 'Filesystem path to export to.', dirname( __DIR__, 2 ) )
			->addOption( 'nocache', null, InputOption::VALUE_NONE, 'Do not cache anything (re-fetch all data).' )
			->addOption( 'nocredits', null, InputOption::VALUE_NONE, 'Do not include the credits list in the exported ebook.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		$timeStart = microtime( true );
		$io = new SymfonyStyle( $input, $output );

		if ( !$input->getOption( 'title' ) ) {
			$io->warning( 'Please provide a title with the --title option.' );
			return Command::FAILURE;
		}

		if ( !$input->getOption( 'lang' ) ) {
			$io->warning( 'Please provide a language code with the --lang option.' );
			return Command::FAILURE;
		}

		$format = $input->getOption( 'format' );
		if ( !in_array( $format, GeneratorSelector::getAllFormats() ) ) {
			$msgFormat = '"%s" is not a valid format. Valid formats are: %s';
			$msg = sprintf( $msgFormat, $format, '"' . implode( '", "', array_keys( GeneratorSelector::getValidFormats() ) ) . '"' );
			$io->warning( $msg );
			return Command::FAILURE;
		}

		$input->validate();
		$options = [
			'images' => true,
			'credits' => !$input->getOption( 'nocredits' ),
		];
		$this->api->setLang( $input->getOption( 'lang' ) );
		if ( $input->getOption( 'nocache' ) || !$this->enableCache ) {
			$io->writeln( 'Caching is disabled.' );
			$this->api->disableCache();
		}
		$creator = BookCreator::forApi( $this->api, $input->getOption( 'format' ), $this->generatorSelector, $this->creditRepo, $this->fileCache );
		$creator->create( $input->getOption( 'title' ), $options, $input->getOption( 'path' ) );

		$io->success( [
			'The ebook has been created: ' . $creator->getFilePath(),
			'Memory usage: ' . ( memory_get_peak_usage( true ) / 1024 / 1024 ) . ' MiB',
			'Total time: ' . round( microtime( true ) - $timeStart, 1 ) . ' seconds',
		] );

		return Command::SUCCESS;
	}
}
