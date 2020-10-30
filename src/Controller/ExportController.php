<?php

namespace App\Controller;

use App\BookCreator;
use App\CreationLog;
use App\FontProvider;
use App\GeneratorSelector;
use App\Refresh;
use App\Util\Api;
use App\Util\Util;
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
// phpcs:ignore
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class ExportController extends AbstractController {

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
		CreationLog $creationLog,
		Api $api,
		LoggerInterface $logger,
		FontProvider $fontProvider
	) {
		// Handle ?refresh=1 for backwards compatibility.
		if ( $request->get( 'refresh', false ) !== false ) {
			return $this->redirectToRoute( 'refresh' );
		}

		$api->setLang( $this->getLang( $request ) );
		$api->setLogger( $logger );

		// If the book title is specified, export it now.
		if ( $request->get( 'page' ) ) {
			return $this->export( $request, $creationLog, $api, $fontProvider );
		}

		$title = $request->get( 'page' );
		$format = $request->get( 'format', 'epub' );
		$font = $this->getFont( $request, $api->getLang(), $fontProvider );
		$images = (bool)$request->get( 'images', true );
		return $this->render( 'export.html.twig', [
			'fonts' => $fontProvider->getPreferred( $font ),
			'font' => $font,
			'formats' => GeneratorSelector::$formats,
			'format' => $format,
			'title' => $title,
			'lang' => $api->getLang(),
			'images' => $images,
		] );
	}

	private function export( Request $request, CreationLog $creationLog, Api $api, FontProvider $fontProvider ) {
		// Get params.
		$title = $request->get( 'page' );
		$format = $request->get( 'format', 'epub' );
		$font = $this->getFont( $request, $api->getLang(), $fontProvider );
		// The `images` checkbox submits as 'false' to disable, so needs extra filtering.
		$images = filter_var( $request->get( 'images', true ), FILTER_VALIDATE_BOOL );

		// Generate ebook.
		$options = [ 'images' => $images, 'fonts' => $font ];
		$creator = BookCreator::forApi( $api, $format, $options, $fontProvider );
		$creator->create( $title );

		// Send file.
		$response = new BinaryFileResponse( $creator->getFilePath() );
		$response->headers->set( 'X-Robots-Tag', 'none' );
		$response->headers->set( 'Content-Description', 'File Transfer' );
		$response->headers->set( 'Content-Type', $creator->getMimeType() );
		$response->setContentDisposition( ResponseHeaderBag::DISPOSITION_ATTACHMENT, $creator->getFilename() );
		$response->deleteFileAfterSend();

		// Log book generation.
		$creationLog->add( $creator->getBook(), $format );

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
		if ( !$font && !in_array( $lang, [ 'fr', 'en', 'de', 'it', 'es', 'pt', 'vec', 'pl', 'nl', 'fa', 'he', 'ar' ] ) ) {
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
			'fonts' => $fontProvider->getPreferred( $this->getFont( $request, $api->getLang(), $fontProvider ) ),
			'font' => $request->get( 'fonts' ),
			'formats' => GeneratorSelector::$formats,
			'format' => $request->get( 'format' ),
			'title' => $request->get( 'page' ),
			'lang' => $api->getLang(),
			'images' => true,
			'messages' => [
				'danger' => [ $message ],
			]
		] );
	}
}
