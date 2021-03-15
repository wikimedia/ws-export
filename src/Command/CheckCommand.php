<?php

namespace App\Command;

use App\BookCreator;
use App\GeneratorSelector;
use App\Repository\CreditRepository;

use App\Util\Api;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use ZipArchive;

class CheckCommand extends Command {

	/** @var string */
	protected static $defaultName = 'app:check';

	/** @var Api */
	private $api;

	/** @var GeneratorSelector */
	private $generatorSelector;

	/** @var CreditRepository */
	private $creditRepo;

	/** @var SymfonyStyle */
	private $io;

	public function __construct( Api $api, GeneratorSelector $generatorSelector, CreditRepository $creditRepo ) {
		parent::__construct();
		$this->api = $api;
		$this->generatorSelector = $generatorSelector;
		$this->creditRepo = $creditRepo;
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
		$creator = BookCreator::forApi( $this->api, 'epub-3', [ 'credits' => false ], $this->generatorSelector, $this->creditRepo );
		$creator->create( $page );
		$jsonOutput = $creator->getFilePath() . '_epubcheck.json';
		$process = new Process( [ 'epubcheck', $creator->getFilePath(), '--json', $jsonOutput ] );
		$process->run();
		$errors = json_decode( file_get_contents( $jsonOutput ), true );
		if ( !isset( $errors['messages'] ) ) {
			throw new Exception( 'Unable to get results of epubcheck.' );
		}
		$hasErrors = false;
		foreach ( $errors['messages'] as $message ) {
			if ( $message['severity'] === 'ERROR' ) {
				$hasErrors = true;
				$lineNum = $message['locations'][0]['line'];
				$colNum = $message['locations'][0]['column'];
				$this->io->warning(
					"Line $lineNum column $colNum"
					. ' of ' . $message['locations'][0]['path'] . ': '
					. $message['message']
				);
				$zip = new ZipArchive();
				$zip->open( $creator->getFilePath() );
				$fileContents = $zip->getFromName( $message['locations'][0]['path'] );
				$lines = explode( "\n", $fileContents );
				$contextLines = array_slice( $lines, $lineNum - 2, 3, true );
				foreach ( $contextLines as $l => $line ) {
					if ( $l + 1 === $lineNum ) {
						$line = substr( $line, 0, $colNum )
							. '<error>' . substr( $line, $colNum, 1 ) . '</error>'
							. substr( $line, $colNum + 1 );
					}
					$this->io->writeln( "<info>" . ( $l + 1 ) . ":</info> $line" );
				}
			}
		}
		if ( !$hasErrors ) {
			$this->io->success( 'No errors found in ' . $page . ' (however, there may be warnings etc.)' );
		}
		if ( file_exists( $jsonOutput ) ) {
			unlink( $jsonOutput );
		}
		if ( file_exists( $creator->getFilePath() ) ) {
			unlink( $creator->getFilePath() );
		}
	}
}
