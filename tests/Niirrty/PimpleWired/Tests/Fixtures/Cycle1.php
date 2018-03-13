<?php


namespace Niirrty\PimpleWired\Tests\Fixtures;


/**
 * @package Niirrty\PimpleWired\Tests\Fixtures
 */
class Cycle1
{

   public function __construct( Cycle2 $c2 ) { }

}

