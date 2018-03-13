<?php


namespace Niirrty\PimpleWired\Tests\Fixtures;


/**
 * @package Niirrty\PimpleWired\Tests\Fixtures
 */
class Cycle2
{

   public function __construct(Cycle3 $c3) {}

}

