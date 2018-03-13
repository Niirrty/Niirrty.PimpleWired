<?php


namespace Niirrty\PimpleWired\Tests\Fixtures;


/**
 * @package Niirrty\PimpleWired\Tests\Fixtures
 */
class Baz
{

   protected $baz;
   protected $bar;
   protected $foo;

   public function __construct( Foo $foo, Bar $bar, $baz )
   {

      $this->foo = $foo;
      $this->bar = $bar;
      $this->baz = $baz;

   }

   public function getFoo()
   {

      return $this->foo;

   }

   public function getBar()
   {

      return $this->bar;

   }

   public function getBaz()
   {

      return $this->baz;

   }

}

