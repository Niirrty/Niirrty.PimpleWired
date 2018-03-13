<?php


namespace Niirrty\PimpleWired\Tests\Fixtures;


/**
 * @package Niirrty\PimpleWired\Tests\Fixtures
 */
class NoCycle2
{
   
   public function __construct( NoCycle3 $nc3 ) { }

}

