<?php

namespace App\Controller;

use App\BookCreator;
use App\Entity\GeneratedBook;
use App\FontProvider;
use App\GeneratorSelector;
use App\Refresh;
use App\Util\Api;
use App\Util\Util;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Locale;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
// phpcs:ignore
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;

class ExportController extends AbstractController {

	/** @var EntityManager */
	private $entityManager;

	/** @var bool */
	private $enableStats;

	/** @var Stopwatch */
	private $stopwatch;

	public function __construct( EntityManagerInterface $entityManager, bool $enableStats, Stopwatch $stopwatch ) {
		$this->entityManager = $entityManager;
		$this->enableStats = $enableStats;
		$this->stopwatch = $stopwatch;
	}

	/**
	 * @Route("/refresh", name="refresh")
	 */
	public function refresh( Request $request, Api $api ) {
		$api->setLang( $this->getLang( $request ) );
		$refresh = new Refresh( $api );
		$refresh->refresh();
		$this->addFlash( 'success', 'The cache is updated for ' . $api->getLang() . ' language.' );
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
		LoggerInterface $logger,
		FontProvider $fontProvider,
		GeneratorSelector $generatorSelector
	) {
		// Handle ?refresh=1 for backwards compatibility.
		if ( $request->get( 'refresh', false ) !== false ) {
			return $this->redirectToRoute( 'refresh' );
		}

		$api->setLang( $this->getLang( $request ) );

		$nocache = (bool)$request->get( 'nocache' );
		if ( $nocache ) {
			$api->disableCache();
		}

		// If the book title is specified, export it now.
		if ( $request->get( 'page' ) ) {
			return $this->export( $request, $api, $fontProvider, $generatorSelector );
		}

		$title = $request->get( 'title' );
		$font = $this->getFont( $request, $api->getLang(), $fontProvider );
		$images = (bool)$request->get( 'images', true );
		return $this->render( 'export.html.twig', [
			'fonts' => $fontProvider->getAll(),
			'font' => $font,
			'formats' => GeneratorSelector::$formats,
			'format' => $this->getFormat( $request ),
			'title' => $title,
			'lang' => $api->getLang(),
			'images' => $images,
			'nocache' => $nocache,
		] );
	}

	private function export(
		Request $request,
		Api $api,
		FontProvider $fontProvider,
		GeneratorSelector $generatorSelector
	) {
		// Get params.
		$page = $request->get( 'page' );
		$format = $this->getFormat( $request );
		$font = $this->getFont( $request, $api->getLang(), $fontProvider );
		// The `images` checkbox submits as 'false' to disable, so needs extra filtering.
		$images = filter_var( $request->get( 'images', true ), FILTER_VALIDATE_BOOL );

		// Start timing.
		if ( $this->enableStats ) {
			$this->stopwatch->start( 'generate-book' );
		}

		// Generate ebook.
		$options = [ 'images' => $images, 'fonts' => $font ];
		$creator = BookCreator::forApi( $api, $format, $options, $generatorSelector );
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
	 * @return string
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
	 * Get the format from the request, defaulting to 'epub-3' if nothing is provided.
	 */
	private function getFormat( Request $request ): string {
		$defaultFormat = 'epub-3';
		$format = $request->get( 'format' );
		if ( !$format ) {
			$format = $defaultFormat;
		}
		if ( !array_key_exists( $format, GeneratorSelector::$formats ) ) {
			$msgFormat = '"%s" is not a valid format. Valid formats are: %s';
			$msg = sprintf( $msgFormat, $format, '"' . implode( '", "', array_keys( GeneratorSelector::$formats ) ) . '"' );
			// Change the requested format to the default,
			// so the exception handler (which also uses this getFormat() method) can select it.
			$request->query->set( 'format', $defaultFormat );
			throw new NotFoundHttpException( $msg );
		}
		return $format;
	}

	/**
	 * Error page handler, to always show the export form with any HTTP error message.
	 *
	 * @param Api $api
	 * @param Exception $exception
	 * @return Response
	 */
	public function error( Request $request, Throwable $exception, Api $api, FontProvider $fontProvider ) {
		// Only handle HTTP and Request exceptions.
		if ( !( $exception instanceof HttpException || $exception instanceof RequestException ) ) {
			throw $exception;
		}
		$message = $exception->getMessage();
		if ( $exception instanceof RequestException ) {
			$exceptionResponse = $exception->getResponse();
			if ( $exceptionResponse ) {
				$message = Util::extractErrorMessage( $exceptionResponse, $exception->getRequest() );
			}
		}
		return $this->render( 'export.html.twig', [
			'fonts' => $fontProvider->getAll(),
			'font' => $request->get( 'fonts' ),
			'formats' => GeneratorSelector::$formats,
			'format' => $this->getFormat( $request ),
			'title' => $request->get( 'title', $request->get( 'page' ) ),
			'lang' => $api->getLang(),
			'images' => true,
			'messages' => [
				'danger' => [ $message ],
			],
			'nocache' => (bool)$request->get( 'nocache' ),
		] );
	}
}
