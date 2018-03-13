<?php


namespace Niirrty\PimpleWired\Tests;


use Niirrty\PimpleWired\WiredContainer;
use PHPUnit\Framework\TestCase;
use Niirrty\PimpleWired\Tests\Fixtures;


class WiredContainerTest extends TestCase
{
   
   protected function tearDown()
   {
      Fixtures\Bar::$INSTANCE_CREATE_COUNTER = 0;
      parent::tearDown();
   }
   
   public function testCreateInstance()
   {
      new WiredContainer();
      $this->assertTrue(true);
   }
   
   public function testCreateClassInstanceWithoutConstructor()
   {
      $container = new WiredContainer();
      $foo = $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Foo' );
      $this->assertInstanceOf( 'Niirrty\PimpleWired\Tests\Fixtures\Foo', $foo );
   }
   
   public function testAccessCreatedClassInstance()
   {
      $container  = new WiredContainer();
      $created = $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Foo' );
      $this->assertSame( $created, $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Foo' ] );
   }
   
   public function testCreateClassInstanceWithAutoResolvedConstructor()
   {
      $container = new WiredContainer();
      $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Bar' );
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Foo',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Bar' ]->getFoo()
      );
   }
   
   public function testCreateClassInstanceWithAdditionalCreateArgs()
   {
      $container = new WiredContainer(
         [],
         [
            'createArgs' => [
               'Niirrty\PimpleWired\Tests\Fixtures\Baz' => [ ':-)' ]
            ]
         ]
      );
      $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Baz' );
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Foo',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Baz' ]->getFoo()
      );
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Bar',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Baz' ]->getBar()
      );
      $this->assertSame( ':-)', $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Baz' ]->getBaz() );
   }

   public function testCreateClassInstanceWithCustomMergeFunction()
   {
      $container = new WiredContainer(
         [],
         [
            'createArgs' => [
               'Niirrty\PimpleWired\Tests\Fixtures\Baz' => function( array $buildArgs,
                  /** @noinspection PhpUnusedParameterInspection */
                                                                     $className,
                  /** @noinspection PhpUnusedParameterInspection */
                                                                     WiredContainer $p )
               {
                  return \array_merge( $buildArgs, [ ':-(' ] );
               }
            ]
         ]
      );
      $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Baz' );
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Foo',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Baz' ]->getFoo()
      );
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Bar',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Baz' ]->getBar()
      );
      $this->assertSame( ':-(', $container['Niirrty\PimpleWired\Tests\Fixtures\Baz']->getBaz());
   }

   /**
    * @expectedException        \InvalidArgumentException
    * @expectedExceptionMessageRegExp /Invalid create args .* Use either array or Callable/
    */
   public function testFailCreateClassInstanceWithInvalidCreateArgs()
   {
      $container = new WiredContainer(
         [],
         [
            'createArgs' => [
               'Niirrty\PimpleWired\Tests\Fixtures\Baz' => ':-|'
            ]
         ]
      );
      $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Baz' );
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Foo',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Baz' ]->getFoo()
      );
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Bar',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Baz' ]->getBar()
      );
      $this->assertSame( ':-|', $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Baz' ]->getBaz() );
   }

   public function testAutoCreateClassInstance()
   {
      $container = new WiredContainer();
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Foo',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Foo' ]
      );
   }

   public function testClassResolvingWithNamespaces()
   {
      $container = new WiredContainer( [], [
         'resolve_namespaces' => [
            'Niirrty\PimpleWired\Tests',
            'Niirrty\PimpleWired\Tests\Fixtures',
         ]
      ] );
      $this->assertInstanceOf( 'Niirrty\PimpleWired\Tests\Fixtures\Foo', $container[ 'Foo' ] );
      $this->assertInstanceOf( 'Niirrty\PimpleWired\Tests\Fixtures\Bar', $container[ 'Fixtures\Bar' ] );
   }

   public function testClassPreRegistering()
   {
      $container = new WiredContainer();
      $container->autoRegister( 'Niirrty\PimpleWired\Tests\Fixtures\Foo' );
      $container->autoRegister( 'Niirrty\PimpleWired\Tests\Fixtures\Bar' );
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Foo',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Foo' ]
      );
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Bar',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Bar' ]
      );
   }

   public function testClassPreRegisteringWithArgs()
   {
      $container = new WiredContainer( [], [
         'createArgs' => [
            'Niirrty\PimpleWired\Tests\Fixtures\Baz' => [ 'red' ]
         ]
      ] );
      $container->autoRegister('Niirrty\PimpleWired\Tests\Fixtures\Baz');
      $container->autoRegister('Niirrty\PimpleWired\Tests\Fixtures\Bar');
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Baz',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Baz' ]
      );
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Bar',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Baz' ]->getBar()
      );
      $this->assertSame( 'red', $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Baz' ]->getBaz() );
   }

   public function testCreateDoesInstantiate()
   {
      $container = new WiredContainer();
      $this->assertSame( 0, Fixtures\Bar::$INSTANCE_CREATE_COUNTER );
      $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Bar' );
      $this->assertSame( 1, Fixtures\Bar::$INSTANCE_CREATE_COUNTER );
   }

   public function testRegisterDoesNotInstantiate()
   {
      $container = new WiredContainer();
      $this->assertSame( 0, Fixtures\Bar::$INSTANCE_CREATE_COUNTER );
      $container->autoRegister( 'Niirrty\PimpleWired\Tests\Fixtures\Bar' );
      $this->assertSame( 0, Fixtures\Bar::$INSTANCE_CREATE_COUNTER );
   }

   /**
    * @expectedException        \InvalidArgumentException
    * @expectedExceptionMessage Identifier "Niirrty\PimpleWired\Tests\Fixtures\Nada" is not defined.
    */
   public function testDoNotAutoResolveNonExistingClass()
   {
      $container = new WiredContainer();
      $nada   = $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Nada' ];
   }

   /**
    * @expectedException        \InvalidArgumentException
    * @expectedExceptionMessage Class 'Niirrty\PimpleWired\Tests\Fixtures\Nada' not exists and could not be found in resolve namespaces
    */
   public function testCreateThrowsExceptionOnNotExistingClass()
   {
      $container = new WiredContainer();
      $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Nada' );
   }

   /**
    * @expectedException        \RuntimeException
    * @expectedExceptionMessage Found circular dependencies: \Niirrty\PimpleWired\Tests\Fixtures\Cycle2 => \Niirrty\PimpleWired\Tests\Fixtures\Cycle3 => \Niirrty\PimpleWired\Tests\Fixtures\Cycle1 => \Niirrty\PimpleWired\Tests\Fixtures\Cycle2
    */
   public function testCircularDependenciesAreRecognized()
   {
      $container = new WiredContainer();
      $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Cycle1' );
   }

   /**
    * @expectedException        \RuntimeException
    * @expectedExceptionMessage Found circular dependencies: \Niirrty\PimpleWired\Tests\Fixtures\Cycle2 => \Niirrty\PimpleWired\Tests\Fixtures\Cycle3 => \Niirrty\PimpleWired\Tests\Fixtures\Cycle1 => \Niirrty\PimpleWired\Tests\Fixtures\Cycle2
    */
   public function testCircularDependenciesAreRecognizedWithArrayAccess()
   {
      $container = new WiredContainer();
      $foo    = $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Cycle1' ];
   }

   /**
    * @expectedException        \RuntimeException
    * @expectedExceptionMessage Found circular dependencies: \Niirrty\PimpleWired\Tests\Fixtures\Cycle1 => \Niirrty\PimpleWired\Tests\Fixtures\Cycle2 => \Niirrty\PimpleWired\Tests\Fixtures\Cycle3 => \Niirrty\PimpleWired\Tests\Fixtures\Cycle1
    */
   public function testCircularDependenciesAreRecognizedOnSecondRun()
   {
      $container        = new WiredContainer();
      $exceptionMessage = '';
      try { $foo = $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Cycle1' ]; }
      catch ( \RuntimeException $e ) { $exceptionMessage = $e->getMessage(); }
      $this->assertContains( 'Found circular dependencies', $exceptionMessage );
      $foo = $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Cycle1' ];
   }

   public function testCircularDependenciesAreResetted()
   {
      $container        = new WiredContainer();
      $exceptionMessage = '';
      try { $foo = $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Cycle1' ]; }
      catch ( \RuntimeException $e ) { $exceptionMessage = $e->getMessage(); }
      $this->assertContains( 'Found circular dependencies', $exceptionMessage );
      unset( $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Cycle1' ] );
      $c1 = \Mockery::mock( 'Niirrty\PimpleWired\Tests\Fixtures\Cycle1' );
      $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Cycle1' ] = $c1;
      $foo = $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Cycle1' ];
      $this->assertSame( $c1, $foo );
   }

   public function testCircularFreeDoesNotThrowException()
   {
      $container = new WiredContainer();
      $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\NoCycle1' );
      $this->assertTrue( true );
   }

   public function testTwoCreationsDoNotTriggerCircularDependencyException()
   {
      $container = new WiredContainer();
      $foo = $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Foo' );
      unset( $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Foo' ] );
      $newFoo = $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Foo' );
      $this->assertInstanceOf( 'Niirrty\PimpleWired\Tests\Fixtures\Foo', $foo );
      $this->assertInstanceOf( 'Niirrty\PimpleWired\Tests\Fixtures\Foo', $newFoo );
   }

   /**
    * @expectedException        \RuntimeException
    * @expectedExceptionMessage Cannot override frozen service 'Niirrty\PimpleWired\Tests\Fixtures\Foo'
    */
   public function testMultiCreationMustNotBeAllowed()
   {
      $container = new WiredContainer();
      $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Foo' );
      $container->create( 'Niirrty\PimpleWired\Tests\Fixtures\Foo' );
   }

   /**
    * @expectedException        \RuntimeException
    * @expectedExceptionMessage Cannot override frozen service 'Foo'
    */
   public function testMultiCreationMustNotBeAllowedWithNamespaces()
   {
      $container = new WiredContainer(
         [],
         [ 'resolve_namespaces' => [ 'Niirrty\PimpleWired\Tests\Fixtures' ] ]
      );
      $container[ 'Foo' ] = 'Bar';
      $container->create( 'Foo' );
   }

   public function testSetNamespacesPostHoc()
   {
      $container = new WiredContainer();
      $exceptionMessage = '';
      try { $container->create( 'Foo' ); }
      catch ( \InvalidArgumentException $e ) { $exceptionMessage = $e->getMessage(); }
      $this->assertSame( 'Class \'Foo\' not exists and could not be found in resolve namespaces', $exceptionMessage );
      $container->setResolveNamespaces( [ 'Niirrty\PimpleWired\Tests\Fixtures' ] );
      $foo = $container->create( 'Foo' );
      $this->assertInstanceOf( 'Niirrty\PimpleWired\Tests\Fixtures\Foo', $foo );
   }

   public function testSetAutoResolvePostHoc()
   {
      $container = new WiredContainer();
      $foo = $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Foo' ];
      $this->assertInstanceOf( 'Niirrty\PimpleWired\Tests\Fixtures\Foo', $foo );
      $container->setAutoResolve( false );
      $exceptionMessage = '';
      try { $bar = $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Bar' ]; }
      catch ( \InvalidArgumentException $e ) { $exceptionMessage = $e->getMessage(); }
      $this->assertSame( 'Identifier "Niirrty\PimpleWired\Tests\Fixtures\Bar" is not defined.', $exceptionMessage );
      $container->setAutoResolve( true );
      $bar = $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Bar' ];
      $this->assertInstanceOf( 'Niirrty\PimpleWired\Tests\Fixtures\Bar', $bar );
   }

   public function testSetCreateArgsPostHoc()
   {
      $container = new WiredContainer();
      $arg = $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Arg' ];
      $this->assertInstanceOf( 'Niirrty\PimpleWired\Tests\Fixtures\Arg', $arg );
      $this->assertNull( $arg->getArg() );
      unset( $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Arg' ] );
      $container->setCreateArgs( [ 'Niirrty\PimpleWired\Tests\Fixtures\Arg' => [ 'ARG' ] ] );
      $arg = $container[ 'Niirrty\PimpleWired\Tests\Fixtures\Arg' ];
      $this->assertInstanceOf( 'Niirrty\PimpleWired\Tests\Fixtures\Arg', $arg );
      $this->assertSame( 'ARG', $arg->getArg() );
   }

   public function testSetAliasesWithInterface()
   {
      $container = new WiredContainer();
      $container->setAliases(
         [ 'Niirrty\PimpleWired\Tests\Fixtures\FooInterface' => 'Niirrty\PimpleWired\Tests\Fixtures\Foo' ]
      );
      $this->assertInstanceOf(
         'Niirrty\PimpleWired\Tests\Fixtures\Foo',
         $container[ 'Niirrty\PimpleWired\Tests\Fixtures\FooInterface' ]
      );
   }

}

