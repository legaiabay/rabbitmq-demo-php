<?php

require_once '../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

class RabbitMQ {
	
	public $connection;
    public $channel;
    public $callback_queue;
    public $response;
    public $corr_id;

    public function __construct($is_consumer = false){

 		// set rabbitmq connection
		$this->connection = new AMQPStreamConnection(
			$_ENV['RABBITMQ_HOST'],
			$_ENV['RABBITMQ_PORT'],
			$_ENV['RABBITMQ_USER'],
			$_ENV['RABBITMQ_PASS'],
			$_ENV['RABBITMQ_VHOST'],
			false, // insist
			'AMQPLAIN', // login method
			null, // login response
			'en_US', // locale
			3.0, // connection timeout
			120, // read write timeout
			null, // context
			false, // keep alive
			60 // heartbeat
		);

		// set channel
		$this->channel = $this->connection->channel();

		// if not a consumer, then set callback queue
		if(!$is_consumer){

			// set callback queue for getting response
			$this->callback_queue = null;
			list($this->callback_queue, ,) = $this->channel->queue_declare(
			    "",
			    false,
			    false,
			    true,
			    false
			);

			$this->channel->basic_consume(
			    $this->callback_queue,
			    '',
			    false,
			    true,
			    false,
			    false,
		    	array(
		    		$this,
		    		"on_response"
		    	)
			);
		}
    }

   
   	// get response callback
    public function on_response($rep){
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }
}


?>