<?php

require_once '../../vendor/autoload.php';
require_once 'rabbitmq.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class GeneratePDFConsumer extends RabbitMQ{

    public function __construct() {
        parent::__construct();
    }

    private function generate_pdf(){
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(40,10,'Hello World!');

        $filename = 'report-' . microtime() . '.pdf';
        $pdf->Output('F', '../public/pdf/' . $filename);
    }

    public function run(){

        // create new queue
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare('queue_generate_pdf', false, false, false, false);

        // create callback function
        $callback = function ($msg) {
            echo "got request! \n";

            // get payload body
            $payload = $msg->body;
            echo $payload . "\n";

            // decode payload json
            $data = json_decode($payload, true);

            // for testing
            sleep(60);

            // do anything here
            $this->generate_pdf();

            // check if message has correlation_id
            // if no correlation_id, then ack immediately
            try {
                $corr_id = $msg->get('correlation_id');
            } catch (\OutOfBoundsException $e) {
                // if no correlation_id then ack
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            
                echo "request processed! \n";
                return;
            }

            // set response
            $response = json_encode(array(
                "status" => "success",
                "code" => 200,
                "message" => "mantap"
            ));

            // set response message
            $res = new AMQPMessage(
                $response,
                array('correlation_id' => $msg->get('correlation_id'))
            );

            // publish response back to sender
            $msg->delivery_info['channel']->basic_publish(
                $res,
                '',
                $msg->get('reply_to')
            );

            // ack rabbitmq message
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

            echo "response sent! \n";
        };

        // message consume limit / prefetch
        $this->channel->basic_qos(null, 2, null);

        // consume and process message requests
        $this->channel->basic_consume('queue_generate_pdf', '', false, false, false, false, $callback);

        // keep runtime alive
        while (count($this->channel->callbacks)) {
            try {
                echo "waiting \n";
                $this->channel->wait();
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                // Do nothing.
                echo "error 1 \n";
                echo $e->getMessage();
                break;
            } catch (\PhpAmqpLib\Exception\AMQPRuntimeException $e) {
                // Do nothing.
                echo "error 2 \n";
                echo $e->getMessage();
                break;
            }
        }

        // close connection
        $this->channel->close();
        $this->connection->close();
    }
}


// run consumer
$consumer = new GeneratePDFConsumer();
$consumer->run();

?>