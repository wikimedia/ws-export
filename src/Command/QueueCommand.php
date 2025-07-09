<?php

namespace App\Command;

use App\Book;
use App\BookStorage;
use App\Entity\GeneratedBook;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class QueueCommand extends Command implements SignalableCommandInterface {
	private BookStorage $bookStorage;
	private bool $enableStats;
	private Stopwatch $stopwatch;
	private EntityManagerInterface $entityManager;
	private bool $shouldExit = false;

	public function __construct( BookStorage $bookStorage, bool $enableStats, Stopwatch $stopwatch, EntityManagerInterface $entityManager ) {
		parent::__construct( 'app:queue' );
		$this->bookStorage = $bookStorage;
		$this->enableStats = $enableStats;
		$this->stopwatch = $stopwatch;
		$this->entityManager = $entityManager;
	}

	protected function configure(): void {
		$this->setDescription( 'Process the queue.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int {
		while ( !$this->shouldExit ) {
			foreach ( $this->bookStorage->getQueue() as $bookData ) {
				$this->export( $bookData );
			}
			sleep( 1 );
		}
		return Command::SUCCESS;
	}

	public function getSubscribedSignals(): array
	{
		return [SIGINT, SIGTERM];
	}

	public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
	{
		$this->shouldExit = true;
		return $signal;
	}

	private function export( array $bookData ) {
		// Start timing.
		if ( $this->enableStats ) {
			$this->stopwatch->start( 'generate-book' );
		}

		// Export the book.
		$this->bookStorage->export(
			$bookData['lang'],
			$bookData['title'],
			$bookData['format'],
			(bool)$bookData['images'],
			(bool)$bookData['credits'],
			$bookData['font'],
		);

		// Log book generation.
		if ( $this->enableStats ) {
			try {
				$book = new Book();
				$book->lang = $bookData['lang'];
				$book->title = $bookData['title'];
				$genBook = new GeneratedBook( $book, $bookData['format'], $this->stopwatch->stop( 'generate-book' ) );
				$this->entityManager->persist( $genBook );
				$this->entityManager->flush();
			} catch ( DriverException $e ) {
				// There was an error writing to tools-db.
				// Silently ignore as this shouldn't prevent the book from being downloaded.
			}
		}
	}
}
