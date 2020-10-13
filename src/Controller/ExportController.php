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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
		FontProvider $fontProvider,
		BookCreator $bookCreator
	) {
		// Handle ?refresh=1 for backwards compatibility.
		if ( $request->get( 'refresh', false ) !== false ) {
			return $this->redirectToRoute( 'refresh' );
		}

		$bookCreator->setTitle( $request->get( 'page' ) );
		$bookCreator->setFormat( $request->get( 'format' ) );
		$bookCreator->setFont( $request->get( 'font' ) );
		$bookCreator->setLang( $request->get( 'lang' ) );

		// The `images` checkbox submits as 'false' to disable, so needs extra filtering.
		$images = filter_var( $request->get( 'images', true ), FILTER_VALIDATE_BOOL );
		$bookCreator->setIncludeImages( $images );

		// If the book title is specified, export it now.
		if ( $request->get( 'page' ) ) {
			return $this->export( $bookCreator, $creationLog );
		}

		return $this->render( 'export.html.twig', [
			'fonts' => $fontProvider->getPreferred( $request->get( 'fonts' ) ),
			'font' => $bookCreator->getFont(),
			'formats' => GeneratorSelector::$formats,
			'format' => $bookCreator->getFormat(),
			'title' => $bookCreator->getTitle(),
			'lang' => $bookCreator->getLang(),
			'images' => $bookCreator->getIncludeImages(),
		] );
	}

	private function export( BookCreator $bookCreator, CreationLog $creationLog ) {
		// Create the book.
		$bookCreator->create();

		// Prepare the file response.
		$fileResponse = new BinaryFileResponse( $bookCreator->getFilePath() );
		$fileResponse->headers->set( 'X-Robots-Tag', 'none' );
		$fileResponse->headers->set( 'Content-Description', 'File Transfer' );
		$fileResponse->headers->set( 'Content-Type', $bookCreator->getMimeType() );
		$filename = $bookCreator->getTitle() . '.' . $bookCreator->getFileExtension();
		$fileResponse->setContentDisposition( ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename );
		$fileResponse->deleteFileAfterSend();

		// Log book generation.
		$creationLog->add( $bookCreator->getBook(), $bookCreator->getFormat() );

		return $fileResponse;
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
			'fonts' => $fontProvider->getPreferred( $request->get( 'fonts' ) ),
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
