<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'admin', 'pass');
        $channel = $connection->channel();

        $channel->queue_declare('exports_queue', false, true, false, false);

        $data = "Some data to export a book";
        $msg = new AMQPMessage(
            $data,
            array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
        );

        $channel->basic_publish($msg, '', 'exports_queue');

        $channel->close();
        $connection->close();

        $io = new SymfonyStyle( $input, $output );
        $io->success(" [x] Sent \n");

        return Command::SUCCESS;
    }
}