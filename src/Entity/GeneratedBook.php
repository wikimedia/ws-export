<?php

namespace App\Entity;

use App\Book;
use App\Repository\GeneratedBookRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * The GeneratedBook is the log of exports.
 */
#[ORM\Entity( repositoryClass: GeneratedBookRepository::class )]
#[ORM\Table( name: 'books_generated' )]
#[ORM\Index( name: 'time', columns: [ 'time' ] )]
#[ORM\Index( name: 'lang', columns: [ 'lang' ] )]
class GeneratedBook {

	/**
	 * @var int
	 */
	#[ORM\Id()]
	#[ORM\GeneratedValue()]
	#[ORM\Column( type: 'integer' )]
	private $id;

	/**
	 * @var DateTime Date and time of the export.
	 */
	#[ORM\Column( type: 'datetime' )]
	private $time;

	/**
	 * @var string Language code.
	 */
	#[ORM\Column( name: 'lang', type: 'string', length: 10 )]
	private $lang;

	/**
	 * @var string Title of work.
	 */
	#[ORM\Column( name: 'title', type: 'string', length: 255 )]
	private $title;

	/**
	 * @var string Format such as 'epub', 'pdf', etc.
	 */
	#[ORM\Column( name: 'format', type: 'string', length: 10 )]
	private $format;

	/**
	 * @var int|null The book generation duration in seconds.
	 */
	#[ORM\Column( name: 'duration', type: 'integer', length: null, precision: null, scale: null, nullable: true )]
	private $duration;

	/**
	 * GeneratedBook constructor.
	 * @param Book $book
	 * @param string $format
	 * @param StopwatchEvent|null $duration
	 */
	public function __construct( Book $book, string $format, ?StopwatchEvent $duration = null ) {
		$this->setTime( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) );
		$this->setLang( $book->lang );
		$this->setTitle( $book->title );
		$this->setFormat( $format );
		if ( $duration ) {
			// Round up to the nearest second.
			$this->setDuration( ceil( $duration->getDuration() / 1000 ) );
		}
	}

	/**
	 * @return int|null
	 */
	public function getId(): ?int {
		return $this->id;
	}

	/**
	 * @return DateTime
	 */
	public function getTime(): DateTime {
		return $this->time;
	}

	/**
	 * @param DateTime $time
	 */
	public function setTime( DateTime $time ): void {
		$this->time = $time;
	}

	/**
	 * @return string
	 */
	public function getLang(): string {
		return $this->lang;
	}

	/**
	 * @param string $lang
	 */
	public function setLang( string $lang ): void {
		$this->lang = $lang;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function setTitle( string $title ): void {
		$this->title = htmlspecialchars_decode( $title );
	}

	/**
	 * @return string
	 */
	public function getFormat(): string {
		return $this->format;
	}

	/**
	 * @param string $format
	 */
	public function setFormat( string $format ): void {
		$this->format = $format;
	}

	/**
	 * Get the generation duration in seconds.
	 * @return int|null
	 */
	public function getDuration(): ?int {
		return $this->duration;
	}

	/**
	 * @param int|null $duration The duration in seconds.
	 */
	public function setDuration( ?int $duration ): void {
		$this->duration = $duration;
	}
}
