<?php

namespace App\Controller;

use App\BookCreator;
use App\CreationLog;
use App\Exception\HttpException;
use App\FontProvider;
use App\GeneratorSelector;
use App\Refresh;
use App\Util\Api;
use App\Util\Util;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends AbstractController {

	/**
	 * @Route("/refresh", name="refresh")
	 */
	public function refresh( Request $request, Api $api ) {
		$refresh = new Refresh( $api );
		$refresh->refresh();
		$this->addFlash( 'success', 'The cache is updated for ' . $api->lang . ' language.' );
		return $this->redirectToRoute( 'home' );
	}

	/**
	 * The main export form.
	 * This route should support the following query string parameters: page, format, images, fonts, refresh.
	 * @Route("/", name="home")
	 * @Route("book.php")
	 * @Route("tool/book.php")
	 */
	public function home( Request $request, CreationLog $creationLog, Api $api, LoggerInterface $logger ) {
		// Handle ?refresh=1 for backwards compatibility.
		if ( $request->get( 'refresh', false ) !== false ) {
			return $this->redirectToRoute( 'refresh' );
		}

		// If the book title is specified, export it now.
		$messages = [];
		$response = new Response();
		if ( $request->get( 'page' ) ) {
			try {
				return $this->export( $request, $creationLog, $logger );
			} catch ( Exception $exception ) {
				$code = 500;
				$message = 'Internal Server Error';
				$error = $exception->getMessage();
				$doLog = true;
				if ( $exception instanceof HttpException ) {
					$parts = preg_split( '/[\r\n]+/', $exception->getMessage(), 2 );
					$code = $exception->getCode();
					$message = $parts[ 0 ];
					// 404's are quite popular, not logging them
					$doLog = $exception->getCode() !== 404;
				} elseif ( $exception instanceof RequestException ) {
					$exceptionResponse = $exception->getResponse();
					if ( $exceptionResponse ) {
						$error = Util::extractErrorMessage( $exceptionResponse, $exception->getRequest() ) ?: $error;
					}
				}
				if ( $doLog ) {
					$logger->error( Util::formatException( $exception ) );
				}
				$response->setStatusCode( $code, $message );
				$error = nl2br( htmlspecialchars( $error ) );
				$messages['danger'] = [ $error ];
			}
		}

		$title = $request->get( 'page' );
		$format = $request->get( 'format', 'epub' );
		$font = $this->getFont( $request, $api->lang );
		$images = (bool)$request->get( 'images', true );
		return $this->render( 'export.html.twig', [
			'fonts' => FontProvider::getList(),
			'font' => $font,
			'formats' => GeneratorSelector::$formats,
			'format' => $format,
			'title' => $title,
			'lang' => $api->lang,
			'images' => $images,
			'messages' => $messages,
		], $response );
	}

	private function export( Request $request, CreationLog $creationLog, LoggerInterface $logger ) {
		// Get params.
		$api = new Api( $request->get( 'lang' ) );
		$api->setLogger( $logger );
		$title = $request->get( 'page' );
		$format = $request->get( 'format', 'epub' );
		$font = $this->getFont( $request, $api->lang );
		// The `images` checkbox submits as 'false' to disable, so needs extra filtering.
		$images = filter_var( $request->get( 'images', true ), FILTER_VALIDATE_BOOL );

		// Generate ebook.
		$options = [ 'images' => $images, 'fonts' => $font ];
		$creator = BookCreator::forApi( $api, $format, $options );
		list( $book, $file ) = $creator->create( $title );

		// Send file.
		$response = new BinaryFileResponse( $file );
		$response->headers->set( 'X-Robots-Tag', 'none' );
		$response->headers->set( 'Content-Description', 'File Transfer' );
		$response->headers->set( 'Content-Type', $creator->getMimeType() );
		$response->setContentDisposition( ResponseHeaderBag::DISPOSITION_ATTACHMENT,  $title . '.' . $creator->getExtension() );
		$response->deleteFileAfterSend();

		// Log book generation.
		$creationLog->add( $book, $format );

		return $response;
	}

	/**
	 * Get a font name from the given request, falling back to the default (which depends on the language).
	 *
	 * @param Request $request The current request.
	 * @param string $lang A language code.
	 * @return string
	 */
	private function getFont( Request $request, $lang ): ?string {
		// Default font for non-latin languages.
		$font = $request->get( 'fonts', '' );
		if ( !$font && !in_array( $lang, [ 'fr', 'en', 'de', 'it', 'es', 'pt', 'vec', 'pl', 'nl', 'fa', 'he', 'ar' ] ) ) {
			$font = 'freeserif';
		}
		return $font;
	}
}
