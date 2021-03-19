<?php

namespace App\Controller;

use App\BookCreator;
use App\Entity\GeneratedBook;
use App\Exception\WsExportException;
use App\FontProvider;
use App\GeneratorSelector;
use App\Refresh;
use App\Repository\CreditRepository;
use App\Util\Api;
use App\Wikidata;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Krinkle\Intuition\Intuition;
use Locale;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
// phpcs:ignore
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;

class ExportController extends AbstractController {

	/** @var EntityManager */
	private $entityManager;

	/** @var bool */
	private $enableStats;

	/** @var bool */
	private $enableCache;

	/** @var Stopwatch */
	private $stopwatch;

	/** @var Intuition */
	private $intuition;

	/** @var Wikidata */
	private $wikidata;

	public function __construct(
		EntityManagerInterface $entityManager, bool $enableStats, bool $enableCache, Stopwatch $stopwatch, Intuition $intuition,
		Wikidata $wikidata
	) {
		$this->entityManager = $entityManager;
		$this->enableStats = $enableStats;
		$this->enableCache = $enableCache;
		$this->stopwatch = $stopwatch;
		$this->intuition = $intuition;
		$this->wikidata = $wikidata;
	}

	/**
	 * @Route("/refresh", name="refresh")
	 */
	public function refresh( Request $request, Api $api, CacheItemPoolInterface $cacheItemPool ) {
		$api->setLang( $this->getLang( $request ) );
		$refresh = new Refresh( $api, $cacheItemPool );
		$refresh->refresh();
		$this->addFlash( 'success', $this->intuition->msg( 'cache-updated', [ 'variables' => [ $api->getLang() ] ] ) );
		return $this->redirectToRoute( 'home' );
	}

	/**
	 * The main export form.
	 * This route should support the following query string parameters: page, format, images, fonts, refresh.
	 * @Route("/", name="home")
	 * @Route("book.php")
	 * @Route("tool/book.php")
	 */
	public function home(
		Request $request,
		Api $api,
		FontProvider $fontProvider,
		GeneratorSelector $generatorSelector,
		CreditRepository $creditRepo
	) {
		// Handle ?refresh=1 for backwards compatibility.
		if ( $request->get( 'refresh', false ) !== false ) {
			return $this->redirectToRoute( 'refresh' );
		}

		$api->setLang( $this->getLang( $request ) );

		$nocache = (bool)$request->get( 'nocache' );
		if ( $nocache || !$this->enableCache ) {
			$api->disableCache();
		}

		// If the book title is specified, export it now.
		$exception = false;
		$response = new Response();
		if ( $request->get( 'page' ) ) {
			try {
				return $this->export( $request, $api, $fontProvider, $generatorSelector, $creditRepo );
			} catch ( WsExportException $ex ) {
				$exception = $ex;
				$response->setStatusCode( $ex->getResponseCode() );
			}
		}

		$font = $this->getFont( $request, $api->getLang(), $fontProvider );
		$credits = (bool)$request->get( 'credits', true );
		$images = (bool)$request->get( 'images', true );
		return $this->render( 'export.html.twig', [
			'fonts' => $fontProvider->getAll(),
			'font' => $font,
			'formats' => GeneratorSelector::getValidFormats(),
			'format' => $this->getFormat( $request ),
			'title' => $this->getTitle( $request ),
			'langs' => $this->getLangs( $request ),
			'lang' => $this->getLang( $request ),
			'credits' => $credits,
			'images' => $images,
			'nocache' => $nocache,
			'enableCache' => $this->enableCache,
			'exception' => $exception,
		], $response );
	}

	private function export(
		Request $request,
		Api $api,
		FontProvider $fontProvider,
		GeneratorSelector $generatorSelector,
		CreditRepository $creditRepo
	) {
		// Get params.
		$page = $request->get( 'page' );
		$format = $this->getFormat( $request );
		$font = $this->getFont( $request, $api->getLang(), $fontProvider );
		// The `credits` checkbox submits as 'false' to disable, so needs extra filtering.
		$credits = filter_var( $request->get( 'credits', true ), FILTER_VALIDATE_BOOL );
		// The `images` checkbox submits as 'false' to disable, so needs extra filtering.
		$images = filter_var( $request->get( 'images', true ), FILTER_VALIDATE_BOOL );

		// Start timing.
		if ( $this->enableStats ) {
			$this->stopwatch->start( 'generate-book' );
		}

		// Generate ebook.
		$options = [ 'images' => $images, 'fonts' => $font, 'credits' => $credits ];
		$creator = BookCreator::forApi( $api, $format, $options, $generatorSelector, $creditRepo );
		$creator->create( $page );

		// Send file.
		$response = new BinaryFileResponse( $creator->getFilePath() );
		$response->headers->set( 'X-Robots-Tag', 'none' );
		$response->headers->set( 'Content-Description', 'File Transfer' );
		$response->headers->set( 'Content-Type', $creator->getMimeType() );
		$response->setContentDisposition( ResponseHeaderBag::DISPOSITION_ATTACHMENT, $creator->getFilename() );
		$response->deleteFileAfterSend();

		// Log book generation.
		if ( $this->enableStats ) {
			$genBook = new GeneratedBook( $creator->getBook(), $format, $this->stopwatch->stop( 'generate-book' ) );
			$this->entityManager->persist( $genBook );
			$this->entityManager->flush();
		}

		return $response;
	}

	/**
	 * Get a font name from the given request, falling back to the default (which depends on the language).
	 *
	 * @param Request $request The current request.
	 * @param string $lang A language code.
	 * @return string|null
	 */
	private function getFont( Request $request, $lang, FontProvider $fontProvider ): ?string {
		// Default font for non-latin languages.
		$font = $fontProvider->resolveName( $request->get( 'fonts' ) );
		if ( !$font && !in_array( $lang, [ 'fr', 'en', 'de', 'it', 'es', 'pt', 'vec', 'pl', 'nl', 'fa', 'he', 'ar', 'zh', 'jp', 'kr' ] ) ) {
			$font = 'FreeSerif';
		}
		if ( !$fontProvider->getOne( $font ) ) {
			$font = '';
		}
		return $font;
	}

	/**
	 * Get the Wikisource language from the URL or based on the user's Accept header.
	 */
	private function getLang( Request $request ): string {
		$lang = $request->get( 'lang' );
		if ( !$lang ) {
			$localInfo = Locale::parseLocale( $request->getPreferredLanguage() );
			$lang = $localInfo['language'] ?? '';
		}
		return strtolower( $lang );
	}

	/**
	 * @return string[]
	 */
	private function getLangs( Request $request ): array {
		$langs = $this->wikidata->getWikisourceLangs( $this->intuition->getLang() );
		$lang = $this->getLang( $request );
		if ( !isset( $langs[ $lang ] ) ) {
			$langs[ $lang ] = $lang;
		}
		return $langs;
	}

	/**
	 * Get the format from the request, defaulting to 'epub-3' if nothing is provided.
	 */
	private function getFormat( Request $request ): string {
		$defaultFormat = 'epub-3';
		$format = $request->get( 'format' );
		if ( !$format ) {
			$format = $defaultFormat;
		}
		return $format;
	}

	/**
	 * Get the page title from either the 'title' or 'page' parameters of the request.
	 */
	private function getTitle( Request $request ): string {
		// It doesn't always make sense to fall back to the 'page' parameter, because that's what prompts the export,
		// but for error pages it's useful.
		return ucfirst( str_replace( '_', ' ', $request->get( 'title', $request->get( 'page' ) ) ) );
	}

}
