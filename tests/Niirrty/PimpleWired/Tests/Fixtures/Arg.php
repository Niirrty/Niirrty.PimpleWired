<?php


namespace Niirrty\PimpleWired\Tests\Fixtures;


/**
 * @package Niirrty\PimpleWired\Tests\Fixtures
 */
class Arg
{

   protected $arg;

   public function __construct( $arg = null )
   {

      $this->arg = $arg;

   }

   public function getArg()
   {
      return $this->arg;
   }

}

