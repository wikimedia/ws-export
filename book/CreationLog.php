<?php

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2012 Thomas Pellissier Tanon
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
 */
class CreationLog {

	/**
	 * @var PDO
	 */
	private $pdo;

	private function __construct() {
		global $wsexportConfig;

		$this->pdo = new PDO( 'sqlite:' . $wsexportConfig['tempPath'] . '/logs.sqlite' );
		$this->createTable();
	}

	private function createTable() {
		$this->pdo->exec(
			'CREATE TABLE IF NOT EXISTS `creation` (
				`lang` VARCHAR(40) NOT NULL,
				`title` VARCHAR(200) NOT NULL,
				`format` VARCHAR(10) NOT NULL,
				`time` DATETIME  NOT NULL
			);'
		);
	}

	public function add( Book $book, $format ) {
		$this->pdo->prepare(
			'INSERT INTO `creation` (`lang`, `title`, `format`, `time`) VALUES (:lang, :title, :format, datetime("now"));'
		)->execute( array(
			'lang' => $book->lang, 'title' => $book->title, 'format' => $format
		) );
	}

	public function getTypeAndLangStats( $month, $year ) {
		$month = ( $month < 10 ) ? '0' . $month : $month;
		$stats = array();

		$cursor = $this->pdo->prepare(
			'SELECT `format`, `lang`, count(1) AS `number` FROM `creation` WHERE `time` BETWEEN :from AND :to GROUP BY `format`, `lang`'
		);
		$cursor->execute( array(
			'from' => $year . '-' . $month . '-00', 'to' => $year . '-' . $month . '-31'
		) );

		foreach( $cursor as $row ) {
			$stats[$row['format']][$row['lang']] = $row['number'];
		}

		return $stats;
	}

	public static function singleton() {
		static $self;

		if( $self === null ) {
			$self = new CreationLog();
		}

		return $self;
	}
}
