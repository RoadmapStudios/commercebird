<?php


use Isolated\Symfony\Component\Finder\Finder;

return array(

	'prefix'                  => 'CommerceBird\\DependencyInjection',
	'finders'                 => array(
		Finder::create()->files()->in( 'libraries' ),
	),
	'expose-global-constants' => true,
	'expose-global-classes'   => true,
	'expose-global-functions' => true,

);
