<?php

namespace App\Contracts;


interface ImportDriverContract
{
   
    public function run($console);

	public function loadAddressFromFile($path_to_file);
	
}