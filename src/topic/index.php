<?php
	include_once("producer.php");

	$repeat = 10;
	for($i=0; $i<$repeat; $i++){

		$genPdf = new GeneratePDF();
		
		// test : send and wait for callback/response
		// $response = $genPdf->generate_pdf(1);

		// test : just send
		$response = $genPdf->generate_pdf(1, true);

		var_dump($response);
		echo "<br>";
	}
?>