<?php

namespace App\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CoreBundle extends Bundle
{
	public function getParent()
	{
	  return 'FOSUserBundle';
	}
}
