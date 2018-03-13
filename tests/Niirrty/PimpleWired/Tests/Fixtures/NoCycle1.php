<?php


namespace Niirrty\PimpleWired\Tests\Fixtures;


/**
 * @package Niirrty\PimpleWired\Tests\Fixtures
 */
class NoCycle1
{

   public function __construct( NoCycle2 $nc2 ) { }

}

