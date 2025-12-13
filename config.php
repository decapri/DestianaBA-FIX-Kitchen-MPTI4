<?php
	$hostname	= "localhost"; 
	$username	= "root"; 
	$password	= ""; 
	$database	= "kopi_janti"; 
	$port		= 3307; // tambahkan ini

	$koneksi	= new mysqli($hostname, $username, $password, $database, $port);

	if($koneksi->connect_error) { //cek error
		die("Error : ".$koneksi->connect_error);
	}
?>