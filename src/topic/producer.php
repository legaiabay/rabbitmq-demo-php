<?php

require_once '../../vendor/autoload.php';
require_once 'rabbitmq.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class GeneratePDF extends RabbitMQ {

	public function __construct() {
        parent::__construct(true);
    }

 	// request generate pdf
    public function generate_pdf($id, $publish_only = false){

    	// debug only
    	$id = '151200002'; // uniqid();

		// set topic name
    	$topic_name = 'topic_gen_payroll';

    	// define response and correlation id
    	$this->response = null;
        $this->corr_id = uniqid();

    	// declare topic
		$this->channel->exchange_declare($topic_name, 'topic', false, false, false);

		$routing_key = 'gen_payroll.company.' . $id;

		// payload to send (in json format)
		$payload = json_encode(array("id" => 1));

		// if publish only, return true immediately after publish
		// no need to wait for response
		if($publish_only){
			// set message
			$msg = new AMQPMessage($payload);

			// publish message to rabbitmq
			$this->channel->basic_publish($msg, $topic_name, $routing_key);

			return true;
		}

		// set message
		$msg = new AMQPMessage(
			json_encode($payload),
			array(
			    'correlation_id' => $this->corr_id,
			    'reply_to' => $this->callback_queue
			)
		);

		// publish message to rabbitmq
		$this->channel->basic_publish($msg, $topic_name, $routing_key);

		// wait for response
		while (!$this->response) {
			try {
				$timeout = 60; // optional
		        $this->channel->wait(null, false, $timeout);
		    } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
		        echo $e->getMessage();
		        break;
		    } catch (\PhpAmqpLib\Exception\AMQPRuntimeException $e) {
		        echo $e->getMessage();
		        break;
		    }
		}
    }
}

?>