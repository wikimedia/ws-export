<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class ConsumerCommand extends Command {

    protected static $defaultName = 'app:consume';

    private $cache;

    /** @var SymfonyStyle */
    private $io;

    public function __construct()
    {
        parent::__construct();
        $this->cache = new FilesystemAdapter();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'admin', 'pass');
        $channel = $connection->channel();

        $channel->queue_declare('exports_queue', false, false, false, false);

        $this->io->success("[*] Waiting for messages. To exit press CTRL+C");

        // TODO: look into this
        // $channel->basic_qos(null, 3, null);
        $channel->basic_consume(
            'exports_queue', '', false, false, false, false, [$this, 'processMessageCallback']);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return Command::SUCCESS;
    }

    public function processMessageCallback( AMQPMessage $r ) {
        echo ' [x] Received request to export ', $r->body, "\n";

        $jobInProcess = $this->cache->getItem( $r->get('correlation_id'));
        if ($jobInProcess->isHit()) {
            $this->io->error("[*] Book export in process");
            $r->getChannel()->basic_nack($r->getDeliveryTag());

            return;
        }
        $jobInProcess->expiresAfter(30); // for the purpose of testing
        $this->cache->save($jobInProcess);

        $msg = new AMQPMessage( $r->body, [ 'correlation_id' => $r->get('correlation_id')]);

        sleep(5);

        $r->getChannel()->exchange_declare('exports_done', 'fanout', false, false, false);
        $r->getChannel()->basic_publish( $msg, 'exports_done', $r->get('reply_to'));
        $r->getChannel()->basic_ack($r->getDeliveryTag());

        $this->cache->delete($r->get('correlation_id'));
    }
}