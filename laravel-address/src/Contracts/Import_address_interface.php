<?php

namespace App\Contracts;


interface Import_address_interface
{
   
    public function run($console);

	public function loadAddressFromFile($path_to_file);
	
}
