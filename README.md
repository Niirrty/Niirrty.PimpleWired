# Niirrty.PimpleWired

Extends Pimple v3.* with auto wiring features.

Auto wiring means

* Automatic class resolving
* Constructor injection
* Automatic class instantiation

## Example

```php
<?php

namespace Xyz;

include dirname( __DIR__ ) . '/vendor/autoload.php';

use \Niirrty\PimpleWired\WiredContainer;

class Foo
{
   public function __toString() : string
   {
      return '!';
   }
}

class User
{

   protected $_name;
   protected $_foo;

   public function __construct( string $name, Foo $foo )
   {
      $this->_foo  = $foo;
      $this->_name = $name;
   }

   public function __toString() : string
   {
      return $this->_name . $this->_foo;
   }

}

class Hello
{

   /** @type \Xyz\User */
   protected $_user;

   public function __construct( User $world )
   {
      $this->_user = $world;
   }

   public function output()
   {
      \printf( 'Hello %s', $this->_user );
   }

}

$container = new WiredContainer(
   [],
   [
      'resolve_namespaces' => []
   ]
);

$container->setCreateArgs(
   [
      '\Xyz\User' => [ 'World' ]
   ]
);

$container[ '\Xyz\Hello' ]->output();

```

## Disable "Auto Resolve"

If you not want to use the auto resolve feature, you can disable it by:

```php
$container->setAutoResolve( false );
```


## The "Create Args"

This are the parameters, passed/injected as constructor parameters for parameters
where NO type hint to a class is declared.

If the constructor of a specific, required class only define params with type hints of classes, there is no need to
pass some create args:

```php
   public function __construct( Foo $foo, Bar $bar ) //…
```

But if one or more params not uses a type hint or a type hint to some other no class types
 
```php
   public function __construct( Foo $foo, string $something, Bar $bar, $blub = null ) //…
```
 
you must "pre define" the required params $something always and $blub optionally

if the class is \Xyz\Baz define it with

```php
$container->setCreateArgs(
   [
      '\Xyz\Baz' => [ 'Something value', 'Optional blub value' ]
   ]
);
```

* `'Something value'` is passed to the $something parameter
* `'Optional blub value'` is passed to the $blub parameter

The order must be the same like defined by constructor params! 

If there is the need to pass values to params with a type hint class

```php
   public function __construct( Foo $foo, \DateTime $dateTime, Bar $bar, $blub = null ) //…
```

be free to use it:

```php
$container->setCreateArgs(
   [
      '\Xyz\Baz' => [ new \DateTime(), 'Optional blub value' ]
   ]
);
```

## Resolve namespaces

If you want to access your classes also without explicit namespace declaration you have to define it as a
resolve namespace like:

```php
$container->setResolveNamespaces( [ '\Xyz' ] );
```

So no `\Xyz\Baz` must be used:

```php
$container->setCreateArgs(
   [
      'Baz' => [ new \DateTime(), 'Optional blub value' ]
   ]
);
```

## Aliases

Aliases are used to map interfaces to concrete class implementations of the interface.

```php
$container->setAliases( [ '\Xyz\FooInterface' => '\Xyz\Foo' ] );
```