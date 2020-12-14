<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConsumerCommand extends Command {

    protected static $defaultName = 'app:consume';

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'admin', 'pass');
        $channel = $connection->channel();

        $channel->queue_declare('exports_queue', false, true, false, false);

        $io = new SymfonyStyle($input, $output);
        $io->success("[*] Waiting for messages. To exit press CTRL+C\n");

        $callback = function ($msg) {
            echo ' [x] Received ', $msg->body, "\n";
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        // TODO: look into this
        // $channel->basic_qos(null, 3, null);
        $channel->basic_consume('exports_queue', '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        return Command::SUCCESS;
    }
}