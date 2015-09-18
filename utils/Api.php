<?php
/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
 */

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * a base class for communications with Wikisource
 */
class Api {
	const USER_AGENT = 'Wikisource Export/0.1';
	public $lang = '';
	public $domainName = '';

	/**
	 * @var ClientInterface
	 */
	private $client;

	/**
	 * @var string $lang the language code of the Wikisource like 'en' or 'fr'
	 */
	public function __construct( $lang = '', $domainName = '' ) {
		if( $lang == '' ) {
			$this->lang = Api::getHttpLang();
		} else {
			$this->lang = $lang;
		}

		if( $domainName != '' ) {
			$this->domainName = $domainName;
		} elseif( $this->lang == 'www' || $this->lang == '' ) {
			$this->domainName = 'wikisource.org';
			$this->lang = '';
		} elseif( $this->lang == 'wl' || $this->lang == 'wikilivres' ) {
			$this->domainName = 'wikilivres.ca';
			$this->lang = '';
		} else {
			$this->domainName = $this->lang . '.wikisource.org';
		}

		$this->client = new Client([
			'defaults' => ['headers' => ['User-Agent' => self::USER_AGENT]]
		]);
	}

	/**
	 * GET action
	 *
	 * @param string $url the target URL
	 * @param array $options
	 * @return PromiseInterface the body of the result
	 */
	public function getAsync( $url, array $options = [] ) {
		return $this->client->getAsync(
			$url,
			$options
		)->then(
			function( ResponseInterface $response ) {
				if($response->getStatusCode() !== 200 ) {
					throw new HttpException( 'HTTP error ' . $response->getStatusCode(), $response->getStatusCode() );
				}
				return $response->getBody();
			},
			function( RequestException $e ) {
				throw new HttpException( $e->getMessage() );
			}
		);
	}

	/**
	 * API query
	 *
	 * @deprecated Use Api::queryAsync
	 *
	 * @param array $params parameters sent to the api
	 * @return array result of the api query
	 * @throws HttpException
	 */
	public function query( $params ) {
		return $this->queryAsync( $params )->wait();
	}

	/**
	 * API query
	 *
	 * @var array $params an associative array for params send to the api
	 * @return PromiseInterface a Promise with the result array
	 * @throws HttpException
	 */
	public function queryAsync( $params ) {
		$params += [ 'action' => 'query', 'format' => 'json' ];

		return $this->getAsync(
			'https://' . $this->domainName . '/w/api.php',
			[ 'query' => $params ]
		)->then(
			function( $result ) {
				return json_decode( $result, true );
			}
		);
	}

	/**
	 * api query. Give all pages of response
	 * @var array $params an associative array for params send to the api
	 * @return array an array with whe result of the api query
	 * @throws HttpException
	 */
	public function completeQuery( $params ) {
		$data = array();
		do {
			$temp = $this->query( $params );
			if( array_key_exists( 'query-continue', $temp ) ) {
				$keys = array_keys( $temp['query-continue'] );
				$keys2 = array_keys( $temp['query-continue'][$keys[0]] );
				$params[$keys2[0]] = $continue = $temp['query-continue'][$keys[0]][$keys2[0]];
			} else {
				$continue = '';
			}
			$data = array_merge_recursive( $data, $temp );
		} while( $continue );

		return $data;
	}

	/**
	 * @param string $title the title of the page
	 * @return PromiseInterface promise with the content of a page
	 */
	public function getPageAsync( $title ) {
		return $this->queryAsync( [
			'titles' => $title,
			'prop' => 'revisions',
			'rvprop' => 'content',
			'rvparse' => true
		] )->then( function( array $result ) {
			return $this->parseGetPageResponse( $result );
		} );
	}

	private function parseGetPageResponse( $response ) {
		foreach( $response['query']['pages'] as $page ) {
			if( isset( $page['revisions'] ) ) {
				foreach( $page['revisions'] as $revision ) {
					return getXhtmlFromContent( $this->lang, $revision['*'], $page['title'] );
				}
			}
		}

		throw new HttpException( 'Page revision not found', 404 );
	}

	/**
	 * @var $title the title of the page
	 * @return the content of a page
	 */
	public function getPage( $title ) {
		$response = $this->query( array(
			'titles' => $title,
			'prop' => 'revisions',
			'rvprop' => 'content',
			'rvparse' => true
		) );

		return $this->parseGetPageResponse( $response );
	}

	/**
	 * @var string $url the url
	 * @return string the file content
	 */
	public function get( $url ) {
		return $this->client->get($url)->getBody()->getContents();
	}

	/**
	 * @var $url the url
	 * @return curl
	 */
	static function getCurl( $url ) {
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_USERAGENT, Api::USER_AGENT );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 4 );

		return $ch;
	}

	/**
	 * @return the lang of the Wikisource used
	 */
	public static function getHttpLang() {
		$lang = '';
		if( isset( $_GET['lang'] ) ) {
			$lang = $_GET['lang'];
		} else {
			if( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
				$langs = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
				if( isset( $langs[0] ) ) {
					$langs = explode( '-', $langs[0] );
					$lang = $langs[0];
				}
			}
		}

		return strtolower( $lang );
	}

	/**
	 * @return the url encoded like mediawiki does.
	 */
	public static function mediawikiUrlEncode( $url ) {
		$search = array( '%21', '%24', '%28', '%29', '%2A', '%2C', '%2D', '%2E', '%2F', '%3A', '%3B', '%40' );
		$replace = array( '!', '$', '(', ')', '*', ',', '-', '.', '/', ':', ';', '@' );

		return str_replace( $search, $replace, urlencode( str_replace( ' ', '_', $url ) ) );
	}
}
