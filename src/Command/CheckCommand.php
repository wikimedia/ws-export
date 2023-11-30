<?php

namespace App\Command;

use App\BookCreator;
use App\EpubCheck\EpubCheck;
use App\FileCache;
use App\GeneratorSelector;
use App\Repository\CreditRepository;
use App\Util\Api;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckCommand extends Command {

	/** @var string */
	protected static $defaultName = 'app:check';

	/** @var Api */
	private $api;

	/** @var GeneratorSelector */
	private $generatorSelector;

	/** @var CreditRepository */
	private $creditRepo;

	/** @var FileCache */
	private $fileCache;

	/** @var EpubCheck */
	private $epubCheck;

	/** @var SymfonyStyle */
	private $io;

	public function __construct( Api $api, GeneratorSelector $generatorSelector, CreditRepository $creditRepo, FileCache $fileCache, EpubCheck $epubCheck ) {
		parent::__construct();
		$this->api = $api;
		$this->generatorSelector = $generatorSelector;
		$this->creditRepo = $creditRepo;
		$this->fileCache = $fileCache;
		$this->epubCheck = $epubCheck;
	}

	protected function configure() {
		$randCount = 10;
		$this->setDescription(
			'Run epubcheck on books.'
			. " With no options set, this will check $randCount random books from English Wikisource."
			. ' Note that the random 10 will be cached (for repeatability) unless you use <info>--nocache</info>.'
		);
		$ignored = 'Ignored if <info>--title</info> is used.';
		$this
			->addOption( 'lang', 'l', InputOption::VALUE_REQUIRED, 'Wikisource language code.', 'en' )
			->addOption( 'nocache', null, InputOption::VALUE_NONE, 'Do not cache anything (re-fetch all data).' )
			->addOption( 'title', 't', InputOption::VALUE_REQUIRED, 'Wiki page name of a single work to check.' )
			->addOption( 'count', 'c', InputOption::VALUE_REQUIRED, "How many random pages to check. $ignored", $randCount )
			->addOption( 'namespaces', 's', InputOption::VALUE_REQUIRED, "Pipe-delimited namespace IDs. $ignored" );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		$this->io = new SymfonyStyle( $input, $output );

		$this->api->setLang( $input->getOption( 'lang' ) );

		if ( $input->getOption( 'nocache' ) ) {
			$this->api->disableCache();
		}

		// Turn off the annoying HTTP logging output unless -v or -vv is used.
		if ( !in_array( $output->getVerbosity(), [ OutputInterface::VERBOSITY_VERBOSE, OutputInterface::VERBOSITY_VERY_VERBOSE ] ) ) {
			$this->api->disableLogging();
		}

		// Export and check the books.
		foreach ( $this->getPages( $input ) as $page ) {
			$this->check( $page );
		}

		return Command::SUCCESS;
	}

	/**
	 * Get list of page names to check.
	 * @return string[]
	 */
	private function getPages( InputInterface $input ) {
		// Full list of pages to check.
		$pages = [];

		// Get title option if it's provided.
		$title = $input->getOption( 'title' );
		if ( $title ) {
			$pages[] = $title;
		}

		// Otherwise, get some random pages.
		if ( !$pages ) {
			// Find content namespaces.
			$namespaces = $input->getOption( 'namespaces' );
			if ( $namespaces === null ) {
				$response = $this->api->queryAsync( [ 'meta' => 'siteinfo', 'siprop' => 'namespaces|namespacealiases' ] )->wait();
				$contentNamespaces = [ 0 ];
				foreach ( $response['query']['namespaces'] ?? [] as $nsInfo ) {
					if ( isset( $nsInfo['content'] ) ) {
						$contentNamespaces[] = $nsInfo['id'];
					}
				}
				$namespaces = implode( '|', $contentNamespaces );
			}
			$randomPages = $this->api->queryAsync( [
				'list' => 'random',
				'rnnamespace' => $namespaces,
				'rnlimit' => $input->getOption( 'count' ),
			] )->wait();
			foreach ( $randomPages['query']['random'] ?? [] as $pageInfo ) {
				$pages[] = $pageInfo['title'];
			}
			$this->io->writeln( 'Retrieved ' . count( $pages ) . ' random pages.' );
		}
		return $pages;
	}

	/**
	 * Download a book from Wikisource and run it through epubcheck.
	 * @param string $page
	 */
	private function check( string $page ) {
		$this->io->section( 'https://' . $this->api->getDomainName() . '/wiki/' . str_replace( ' ', '_', $page ) );
		$creator = BookCreator::forApi( $this->api, 'epub-3', [ 'credits' => false ], $this->generatorSelector, $this->creditRepo, $this->fileCache );
		$creator->create( $page );
		$results = $this->epubCheck->check( $creator->getFilePath() );
		if ( count( $results ) === 0 ) {
			return;
		}
		$hasErrors = false;
		foreach ( $results as $result ) {
			if ( $result->isError() ) {
				$hasErrors = true;
				$this->io->warning( $result->getMessage() );
				$this->io->writeln( 'In ' . count( $result->getLocations() ) . ' location' . ( count( $result->getLocations() ) > 1 ? 's' : '' ) . ':' );
				foreach ( $result->getLocations() as $locNum => $location ) {
					$this->io->writeln( "    $locNum: <info>$location</info>" );
				}
			}
		}
		if ( !$hasErrors ) {
			$this->io->success( 'No errors found in ' . $page . ' (however, there may be warnings etc.)' );
		}
		if ( file_exists( $creator->getFilePath() ) ) {
			unlink( $creator->getFilePath() );
		}
	}
}
