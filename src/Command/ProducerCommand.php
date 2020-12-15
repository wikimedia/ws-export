<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ProducerCommand extends Command {

    protected static $defaultName = 'app:send';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure() {
		$this->setDescription( 'Export a book.' )
			->addOption( 'title', 't', InputOption::VALUE_REQUIRED, 'Wiki page name of the work to export. Required' );
	}

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle( $input, $output );

		if ( !$input->getOption( 'title' ) ) {
			$io->warning( 'Please provide a title with the --title option.' );
			return Command::FAILURE;
        }

        $response = null;
        $corrId = md5($input->getOption('title'));

        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'admin', 'pass');
        $channel = $connection->channel();

        // set callback channel and exchange
        list($callback_queue, ,) = $channel->queue_declare("", false, false, true, false);
        $channel->exchange_declare('exports_done', 'fanout', false, false, false);
        $channel->queue_bind($callback_queue, 'exports_done');

        $channel->basic_consume($callback_queue, '', false, false, false, false, function( $r ) use ( &$response, $corrId ) {
            if ($r->get('correlation_id') == $corrId ) {
                $response = $r->body;
            }
        });

        // set publish channel
        $channel->queue_declare('exports_queue', false, false, false, false);

        $data = $input->getOption( 'title' );
        $msg = new AMQPMessage(
            $data,
            array(
                'correlation_id' => $corrId,
                'reply_to' => $callback_queue,
           )
        );

        $channel->basic_publish($msg, '', 'exports_queue');

        $io->success(" [x] Sent request to export " . $input->getOption('title') );

        try {
            while (!$response) {
                $channel->wait(null, false, 10);
            }
            // TODO: catch proper exception for timeones
        } catch ( \Exception $e) {
            $io->error(" [x] We are working on your book and it might take a while. Come back here http://somenewurl in a few minutes for an update" );

            $channel->close();
            $connection->close();

            return Command::FAILURE;
            
        }

        $io->success(" [x] ". $response . " was successfully exported" );

        $channel->close();
        $connection->close();

        return Command::SUCCESS;
    }
}