<?php

namespace App\Entity;

use App\Book;
use DateTime;
use DateTimeZone;
// mediawiki-codesniffer apparently can't search annotations for unused classes.
// phpcs:ignore MediaWiki.Classes.UnusedUseStatement.UnusedUse
use Doctrine\ORM\Mapping as ORM;

/**
 * The GeneratedBook is the log of exports.
 * @ORM\Entity(repositoryClass="App\Repository\GeneratedBookRepository")
 * @ORM\Table(
 *     name="books_generated",
 *     indexes={
 * @ORM\Index(name="time", columns={"time"}),
 * @ORM\Index(name="lang", columns={"lang"})
 *     }
 * )
 */
class GeneratedBook {

	/**
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 * @ORM\Column(type="integer")
	 * @var int
	 */
	private $id;

	/**
	 * @ORM\Column(type="datetime")
	 * @var DateTime Time of the export.
	 */
	private $time;

	/**
	 * @ORM\Column(type="string", length=10)
	 * @var string Language code.
	 */
	private $lang;

	/**
	 * @ORM\Column(type="string", length=255)
	 * @var string Title of work.
	 */
	private $title;

	/**
	 * @ORM\Column(type="string", length=10)
	 * @var string Format such as 'epub', 'pdf', etc.
	 */
	private $format;

	/**
	 * GeneratedBook constructor.
	 * @param Book $book
	 * @param string $format
	 */
	public function __construct( Book $book, string $format ) {
		$this->setTime( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) );
		$this->setLang( $book->lang );
		$this->setTitle( $book->title );
		$this->setFormat( $format );
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
}
