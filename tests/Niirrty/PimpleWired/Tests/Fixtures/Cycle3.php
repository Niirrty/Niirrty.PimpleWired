<?php


namespace Niirrty\PimpleWired\Tests\Fixtures;


/**
 * @package Niirrty\PimpleWired\Tests\Fixtures
 */
class Cycle3
{

   public function __construct(Cycle1 $c2) {}

}

