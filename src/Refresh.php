<?php

namespace App;

use App\Util\Api;
use DOMDocument;
use Exception;

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2012 Thomas Pellissier Tanon
 * @license GPL-2.0-or-later
 */
class Refresh {

	protected $api;

	public function __construct( Api $api ) {
		$this->api = $api;
	}

	public function refresh() {
		if ( !is_dir( $this->getTempFileName( '' ) ) ) {
			mkdir( $this->getTempFileName( '' ) );
		}

		$this->getI18n();
		$this->getEpubCssWikisource();
		$this->getAboutXhtmlWikisource();
		$this->getNamespacesList();
	}

	protected function getI18n() {
		$ini = parse_ini_file( dirname( __DIR__ ) . '/resources/i18n.ini' );
		try {
			$response = $this->api->get( 'https://' . $this->api->getLang() . '.wikisource.org/w/index.php?title=MediaWiki:Wsexport_i18n.ini&action=raw&ctype=text/plain' );
			$temp = parse_ini_string( $response );
			if ( $ini != false ) {
				$ini = array_merge( $ini, $temp );
			}
		} catch ( Exception $e ) {
		}
		$this->setTempFileContent( 'i18n.sphp', serialize( $ini ) );
	}

	protected function getEpubCssWikisource() {
		$content = file_get_contents( dirname( __DIR__ ) . '/resources/styles/mediawiki.css' );
		try {
			$content .= "\n" . $this->api->get( 'https://' . $this->api->getLang() . '.wikisource.org/w/index.php?title=MediaWiki:Epub.css&action=raw&ctype=text/css' );
		} catch ( Exception $e ) {
		}
		$this->setTempFileContent( 'epub.css', $content );
	}

	protected function getAboutXhtmlWikisource() {
		try {
			$content = $this->api->getPageAsync( 'MediaWiki:Wsexport_about' )->wait();
		} catch ( Exception $e ) {
			try {
				$oldWikisourceApi = clone $this->api;
				$oldWikisourceApi->setLang( 'www' );
				$content = $oldWikisourceApi->getPageAsync( 'MediaWiki:Wsexport_about' )->wait();
			} catch ( Exception $e ) {
				$content = '';
			}
		}
		if ( $content === '' ) {
			$this->setTempFileContent( 'about.xhtml', '' );
		} else {
			$document = new DOMDocument( '1.0', 'UTF-8' );
			$document->loadXML( $content );
			$parser = new PageParser( $document );
			$document = $parser->getContent( true );
			// Add https to protocol-relative links.
			$aboutHtml = str_replace( 'href="//', 'href="https://', $document->saveXML() );
			// Fully qualify unqualified links.
			$aboutHtml = str_replace( 'href="./', 'href="https://' . $this->api->getDomainName() . '/wiki/', $aboutHtml );
			$this->setTempFileContent( 'about.xhtml', $aboutHtml );
		}
	}

	protected function getNamespacesList() {
		$namespaces = [];
		$response = $this->api->query( [ 'meta' => 'siteinfo', 'siprop' => 'namespaces|namespacealiases' ] );
		foreach ( $response['query']['namespaces'] as $namespace ) {
			if ( array_key_exists( '*', $namespace ) && $namespace['*'] ) {
				$namespaces[] = $namespace['*'];
			}
			if ( array_key_exists( 'canonical', $namespace ) && $namespace['canonical'] ) {
				$namespaces[] = $namespace['canonical'];
			}
		}
		foreach ( $response['query']['namespacealiases'] as $namespaceAlias ) {
			if ( array_key_exists( '*', $namespaceAlias ) ) {
				$namespaces[] = $namespaceAlias['*'];
			}
		}
		$this->setTempFileContent( 'namespaces.sphp', serialize( $namespaces ) );
	}

	protected function setTempFileContent( $name, $content ) {
		return file_put_contents( $this->getTempFileName( $name ), $content );
	}

	protected function getTempFileName( $name ) {
		$cache = FileCache::singleton();

		return $cache->getDirectory() . '/' . $this->api->getLang() . '/' . $name;
	}
}
