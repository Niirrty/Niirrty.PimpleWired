<?php
/**
 * @author         Ni Irrty <niirrty+code@gmail.com>
 * @copyright  (c) 2017, Ni Irrty
 * @license        MIT
 * @since          2018-03-10
 * @version        0.1.0
 */


declare( strict_types = 1 );


namespace Niirrty\PimpleWired;


use Pimple\Container;


class WiredContainer extends Container
{


   // <editor-fold desc="// –––––––   P R I V A T E   F I E L D S   ––––––––––––––––––––––––––––––––––––––">

   /**
    * List of already created instances to find circular dependencies.
    *
    * @var array
    */
   private $_edges;

   // </editor-fold>


   // <editor-fold desc="// –––––––   P R O T E C T E D   F I E L D S   ––––––––––––––––––––––––––––––––––">

   /**
    * The optional list of additional namespaces, used to resolve relative Class names.
    *
    * @var string[]
    */
   protected $_resolveNamespaces;

   /**
    * A associative array with absolute class names and additional, tailing constructor arguments
    *
    * Assuming "\Foo\Bar" has the constructor signature
    *
    * <code>
    * (\Foo\Baz $baz, $someArg )
    * </code>
    *
    * then the following can inject $someArg:
    *
    * <code>
    * [ '\\Vendor\\Foo' => [ 'Arg value' ] ]
    * </code>
    *
    * Alternatively a callback for constructor argument generation can be used:
    *
    * <code>
    * [ '\\Vendor\\Foo' => function ( array $autoArgs, $className, WiredContainer $p)
    *    {
    *       return \array_merge( $autoArgs, [ 'Some Arg' ] );
    *    }
    * ) ]
    * </code>
    *
    * @type string[] List of additional namespaces
    */
   protected $_createArgs;

   /**
    * Whether auto resolving on array access is enabled.
    *
    * @type bool
    */
   protected $_autoResolve;

   /**
    * A associative array with service aliases.
    *
    * It can be used to resolve interfaces to implementations, e.g.:
    *
    * <code>
    * [ '\\Foo\\BarInterface' => '\\Foo\\Bar' ]
    * </code>
    *
    * @type array
    */
   protected $_aliases;

   // </editor-fold>


   // <editor-fold desc="// –––––––   C O N S T R U C T O R   A N D / O R   D E S T R U C T O R   ––––––––">

   /**
    * WiredContainer constructor.
    *
    * @param array $values
    * @param array $config
    */
   public function __construct( array $values = [], array $config = [] )
   {

      // Always call the parent \Pimple\Container constructor
      parent::__construct( $values );

      // Init the class properties/fields
      $this->_resolveNamespaces = [];
      $this->_aliases           = [];
      $this->_createArgs        = [];
      $this->_edges             = [];
      $this->_autoResolve       = true;

      if ( isset( $config[ 'resolve_namespaces' ] ) )
      {
         $this->setResolveNamespaces(
            ! \is_array( $config[ 'resolve_namespaces' ] )
               ? [ $config[ 'resolve_namespaces' ] ]
               : $config[ 'resolve_namespaces' ]
         );
      }
      else if ( isset( $config[ 'resolveNamespaces' ] ) )
      {
         $this->setResolveNamespaces(
            ! \is_array( $config[ 'resolveNamespaces' ] )
               ? [ $config[ 'resolveNamespaces' ] ]
               : $config[ 'resolveNamespaces' ]
         );
      }

      if ( isset( $config[ 'aliases' ] ) )
      {
         $this->setAliases(
            ! \is_array( $config[ 'aliases' ] )
               ? [ $config[ 'aliases' ] ]
               : $config[ 'aliases' ]
         );
      }

      if ( isset( $config[ 'create_args' ] ) && \is_array( $config[ 'create_args' ] ) )
      {
         $this->setCreateArgs( $config[ 'create_args' ] );
      }
      else if ( isset( $config[ 'createArgs' ] ) && \is_array( $config[ 'createArgs' ] ) )
      {
         $this->setCreateArgs( $config[ 'createArgs' ] );
      }

      if ( isset( $config[ 'auto_resolve' ] ) && \is_bool( $config[ 'auto_resolve' ] ) )
      {
         $this->setAutoResolve( $config[ 'auto_resolve' ] );
      }
      else if ( isset( $config[ 'autoResolve' ] ) && \is_bool( $config[ 'autoResolve' ] ) )
      {
         $this->setAutoResolve( $config[ 'autoResolve' ] );
      }

   }

   // </editor-fold>


   // <editor-fold desc="// –––––––   P U B L I C   M E T H O D S   ––––––––––––––––––––––––––––––––––––––">


   // <editor-fold desc="// – – – –   G E T T E R   – – – – – – – – – – –">

   /**
    * Gets a parameter or an object.
    *
    * @param string $id The unique identifier for the parameter or object
    * @return mixed The value of the parameter or an object
    * @throws \InvalidArgumentException If the identifier is not defined
    * @throws \RuntimeException         If there is a circular dependency
    */
   public function offsetGet( $id )
   {

      if ( ! isset( $this[ $id ] ) && $this->_autoResolve && $this->tryResolveClassName( $id, $resolvedClassName ) )
      {
         $this->autoRegister( $id );
         return $this[ $id ];
      }
      else if ( isset( $this[ $id ] ) && ( $error = $this->getCircularEdgeError( $id ) ) )
      {
         throw new \RuntimeException( $error );
      }

      return parent::offsetGet( $id );

   }

   /**
    * @inheritdoc
    */
   public function raw( $id )
   {

      if ( ! $this->offsetExists( $id ) && $this->_autoResolve && $this->tryResolveClassName( $id, $resolvedClassName ) )
      {
         $this->create( $resolvedClassName, false );
      }

      return parent::raw( $id );

   }

   // </editor-fold>


   // <editor-fold desc="// – – – –   S E T T E R   – – – – – – – – – – –">

   /**
    * Sets the optional list of additional namespaces, used to resolve relative Class names.
    *
    * @param array $namespaces
    * @return \Niirrty\PimpleWired\WiredContainer
    * @throws \InvalidArgumentException If a invalid namespace format is used.
    */
   public function setResolveNamespaces( array $namespaces = [] ) : WiredContainer
   {

      // Check if all namespaces use a valid format
      foreach ( $namespaces as $namespace )
      {
         if ( ! \is_string( $namespace ) )
         {
            throw new \InvalidArgumentException(
               'Invalid wired container resolve-namespace format! ' .
               'It must be a string and it must be a valid absolute namespace.'
            );
         }
      }

      $this->_resolveNamespaces = $namespaces;

      return $this;

   }

   /**
    * Sets the optional list of additional namespaces, used to resolve relative Class names.
    *
    * @param array $namespaces
    * @return \Niirrty\PimpleWired\WiredContainer
    * @throws \InvalidArgumentException If a invalid namespace format is used.
    */
   public function addResolveNamespaces( array $namespaces = [] ) : WiredContainer
   {

      // Check if all namespaces use a valid format
      foreach ( $namespaces as $namespace )
      {
         if ( ! \is_string( $namespace ) )
         {
            throw new \InvalidArgumentException(
               'Invalid wired container resolve-namespace format! ' .
               'It must be a string and it must be a valid absolute namespace.'
            );
         }
         if ( ! \in_array( $namespace, $this->_resolveNamespaces, true ) )
         {
            $this->_resolveNamespaces[] = $namespace;
         }
      }

      return $this;

   }

   /**
    * Sets a associative array with service aliases.
    *
    * It can be used to resolve interfaces to implementations, e.g.:
    *
    * <code>
    * [ '\\Foo\\BarInterface' => '\\Foo\\Bar' ]
    * </code>
    *
    * @param array $aliases
    * @return \Niirrty\PimpleWired\WiredContainer
    * @throws \InvalidArgumentException If a invalid alias format is used.
    */
   public function setAliases( array $aliases = [] ) : WiredContainer
   {

      // Check if all namespaces use a valid format
      foreach ( $aliases as $k => $v )
      {
         if ( ! \is_string( $k ) || ! \is_string( $v ) )
         {
            throw new \InvalidArgumentException( 'Invalid wired container alias format!' );
         }
      }

      $this->_aliases = $aliases;

      return $this;

   }

   /**
    * Sets a service alias.
    *
    * It can be used to resolve interfaces to implementations, e.g.:
    *
    * <code>
    * $container->setAlias( '\\Foo\\BarInterface', '\\Foo\\Bar' );
    * </code>
    *
    * @param  string $alias
    * @param  string $className
    * @return \Niirrty\PimpleWired\WiredContainer
    */
   public function setAlias( string $alias, string $className ) : WiredContainer
   {

      $this->_aliases[ $alias ] = $className;

      return $this;

   }

   /**
    * Adds service aliases.
    *
    * It can be used to resolve interfaces to implementations, e.g.:
    *
    * <code>
    * [ '\\Foo\\BarInterface' => '\\Foo\\Bar' ]
    * </code>
    *
    * @param array $aliases
    * @return \Niirrty\PimpleWired\WiredContainer
    * @throws \InvalidArgumentException If a invalid alias format is used.
    */
   public function addAliases( array $aliases = [] ) : WiredContainer
   {

      // Check if all namespaces use a valid format
      foreach ( $aliases as $k => $v )
      {
         if ( ! \is_string( $k ) || ! \is_string( $v ) )
         {
            throw new \InvalidArgumentException( 'Invalid wired container alias format!' );
         }
         $this->_aliases[ $k ] = $v;
      }

      return $this;

   }

   /**
    * Sets a associative array with absolute class names and additional, tailing constructor arguments
    *
    * Assuming "\Foo\Bar" has the constructor signature
    *
    * <code>
    * (\Foo\Baz $baz, $someArg )
    * </code>
    *
    * then the following can inject $someArg:
    *
    * <code>
    * [ '\\Vendor\\Foo' => [ 'Arg value' ] ]
    * </code>
    *
    * Alternatively a callback for constructor argument generation can be used:
    *
    * <code>
    * [ '\\Vendor\\Foo' => function ( array $autoArgs, $className, WiredContainer $p)
    *    {
    *       return \array_merge( $autoArgs, [ 'Some Arg' ] );
    *    }
    * ) ]
    * </code>
    *
    * @param \string[] $createArgs
    * @return \Niirrty\PimpleWired\WiredContainer
    */
   public function setCreateArgs( array $createArgs = [] ) : WiredContainer
   {

      $this->_createArgs = $createArgs;

      return $this;

   }

   /**
    * Sets a absolute class name and additional, tailing constructor arguments
    *
    * Assuming "\Foo\Bar" has the constructor signature
    *
    * <code>
    * (\Foo\Baz $baz, $someArg )
    * </code>
    *
    * then the following can inject $someArg:
    *
    * <code>
    * $container->aetCreateArg( '\\Vendor\\Foo', [ 'Arg value' ] );
    * </code>
    *
    * Alternatively a callback for constructor argument generation can be used:
    *
    * <code>
    * [ '\\Vendor\\Foo' => function ( array $autoArgs, $className, WiredContainer $p)
    *    {
    *       return \array_merge( $autoArgs, [ 'Some Arg' ] );
    *    }
    * ) ]
    * </code>
    *
    * @param  string         $className
    * @param  callable|array $args
    * @return \Niirrty\PimpleWired\WiredContainer
    */
   public function setCreateArg( string $className, $args ) : WiredContainer
   {

      $this->_createArgs[ $className ] = $args;

      return $this;

   }

   /**
    * Sets a associative array with absolute class names and additional, tailing constructor arguments
    *
    * Assuming "\Foo\Bar" has the constructor signature
    *
    * <code>
    * (\Foo\Baz $baz, $someArg )
    * </code>
    *
    * then the following can inject $someArg:
    *
    * <code>
    * [ '\\Vendor\\Foo' => [ 'Arg value' ] ]
    * </code>
    *
    * Alternatively a callback for constructor argument generation can be used:
    *
    * <code>
    * [ '\\Vendor\\Foo' => function ( array $autoArgs, $className, WiredContainer $p)
    *    {
    *       return \array_merge( $autoArgs, [ 'Some Arg' ] );
    *    }
    * ) ]
    * </code>
    *
    * @param \string[] $createArgs
    * @return \Niirrty\PimpleWired\WiredContainer
    */
   public function removeCreateArg( string $className ) : WiredContainer
   {

      unset( $this->_createArgs[ $className ] );

      return $this;

   }

   /**
    * Enable or disable auto resolving.
    *
    * @param bool $autoResolve
    * @return \Niirrty\PimpleWired\WiredContainer
    */
   public function setAutoResolve( bool $autoResolve ) : WiredContainer
   {

      $this->_autoResolve = $autoResolve;

      return $this;

   }

   /**
    * {@inheritdoc}
    */
   public function offsetUnset( $id )
   {

      parent::offsetUnset( $id );

      $classNames = [ $id, self::toAbsoluteClassName( $id ) ];
      if ( $this->tryResolveClassName( $id, $resClass ) )
      {
         $classNames[] = $resClass;
      }

      $classNames = \array_unique( $classNames );

      foreach ( $classNames as $className )
      {
         if ( isset( $this->_edges[ $className ] ) )
         {
            unset( $this->_edges[ $className ] );
         }
      }

   }

   // </editor-fold>


   // <editor-fold desc="// – – – –   O T H E R   – – – – – – – – – – – –">

   /**
    * Manually create a new auto-resolved class instance and return it
    *
    * @param  string $className Absolute class name or partial class name of registered namespaces
    * @param  bool   $return Return the resulting instance?
    * @throws \InvalidArgumentException
    * @throws \RuntimeException
    * @return mixed
    */
   public function create( string $className, bool $return = true )
   {

      $orgClassName = $className;

      if ( ! $this->tryResolveClassName( $className, $clsName ) )
      {
         throw new \InvalidArgumentException(
            "Class '$orgClassName' not exists and could not be found in resolve namespaces"
         );
      }

      // to comply with Pimple2 -> no overwriting (user must manually unset)
      if ( isset( $this[ $clsName ] ) )
      {
         throw new \RuntimeException( "Cannot override frozen service '$clsName'" );
      }
      else if ( isset( $this[ $orgClassName ] ) )
      {
         throw new \RuntimeException( "Cannot override frozen service '$orgClassName'" );
      }

      $copyOfThis = $this;
      $this[ $clsName ] = function () use ( $copyOfThis, $clsName )
      {
         return $copyOfThis->createInstance( $clsName );
      };

      if ( $orgClassName !== $clsName )
      {
         $this[ $orgClassName ] = function () use ( $copyOfThis, $clsName )
         {
            return $copyOfThis[ $clsName ];
         };
      }

      return $return
         ? $this[ $clsName ]
         : null;

   }

   /**
    * Pre register a class instance, but do not create it!
    *
    * The instance will be created on first access.
    *
    * @param  string $className Absolute class name or partial class name of registered namespaces
    * @throws \InvalidArgumentException
    * @throws \RuntimeException
    */
   public function autoRegister( string $className )
   {

      $this->create( $className, false );

   }

   // </editor-fold>


   // </editor-fold>


   // <editor-fold desc="// -------   P R O T E C T E D   M E T H O D S   --------------------------------">

   /**
    * Create a instance of a class.
    *
    * @param string $className The absolute class name.
    * @throws \RuntimeException
    * @return object
    */
   protected function createInstance( string $className )
   {

      $refClass = new \ReflectionClass( $className );

      if ( ! $refClass->hasMethod( '__construct' ) )
      {
         return $refClass->newInstance();
      }

      $this->_edges[ $className ] = [];
      $refConstructor             = $refClass->getMethod( '__construct' );
      $constructorArgs            = $this->buildConstructorParams($refConstructor, $className );

      return $refClass->newInstanceArgs( $constructorArgs );

   }

   /**
    * Builds and returns signature
    *
    * @param \ReflectionMethod $refMethod
    * @param string            $className
    * @throws \RuntimeException
    * @return array
    */
   protected function buildConstructorParams( \ReflectionMethod $refMethod, string $className ) : array
   {

      $args       = [];
      $createArgs = $this->getCreateArgsByClass( $className );
      $caCount    = \count( $createArgs );
      $caIndex    = 0;

      if ( ! isset( $this->_edges[ $className ] ) )
      {
         $this->_edges[ $className ] = [];
      }

      // Loop all reflection parameters of the constructor
      foreach ( $refMethod->getParameters() as $refParam )
      {

         if ( $refParamClass = $refParam->getClass() )
         {

            // The reflection param has a class

            $paramClass                   = static::toAbsoluteClassName( $refParamClass->getName() );
            $this->_edges[ $className ][] = $paramClass;

            if ( $error = $this->getCircularEdgeError( $paramClass ) )
            {
               throw new \RuntimeException( $error );
            }

            if ( $caIndex < $caCount && \is_object( $createArgs[ $caIndex ] ) )
            {

               // The next defined create arg is a object => check if its the required class and if so, use it

               $clsName  = \get_class( $createArgs[ $caIndex ] );
               $clsNames = \array_unique( [
                  $clsName,
                  static::toAbsoluteClassName( $clsName ),
                  static::toRelativeClassName( $clsName )
               ] );
               if ( $paramClass === static::toAbsoluteClassName( $clsName ) )
               {
                  // The next defined create arg is a object of required class => use it
                  $args[] = $createArgs[ $caIndex ];
                  $caIndex++;
                  continue;
               }
            }

            $args[] = isset( $this[ $paramClass ] ) ? $this[ $paramClass ] : $this->create( $paramClass );
            continue;

         }

         if ( $caIndex < $caCount )
         {

            $args[] = $createArgs[ $caIndex ];
            $caIndex++;

         }

      }

      while ( $caIndex < $caCount )
      {
         $args[] = $createArgs[ $caIndex ];
         $caIndex++;
      }

      return $args;

   }

   protected function getCreateArgsByClass( string $className )
   {

      $relClassName = self::toRelativeClassName( $className );
      $absClassName = self::toAbsoluteClassName( $className );

      $args = [];

      if ( isset( $this->_createArgs[ $absClassName ] ) )
      {
         $args = $this->_createArgs[ $absClassName ];
      }

      if ( isset( $this->_createArgs[ $relClassName ] ) )
      {
         $args = $this->_createArgs[ $relClassName ];
      }

      if ( \is_callable( $args ) )
      {
         return $args( $className, $this );
      }

      if ( \is_array( $args ) )
      {
         return $args;
      }

      return [];

   }

   /**
    * Tries to resolve a class for defined class name.
    *
    * @param string $className
    * @param string $outClassName Return the resolved class name if the method return true
    * @return bool
    */
   protected function tryResolveClassName( string $className, ?string &$outClassName = '' ) : bool
   {

      $relClassName = self::toRelativeClassName( $className );
      $absClassName = self::toAbsoluteClassName( $className );

      if ( \array_key_exists( $relClassName, $this->_aliases ) )
      {
         $className = $this->_aliases[ $relClassName ];
      }

      if ( \array_key_exists( $absClassName, $this->_aliases ) )
      {
         $className = $this->_aliases[ $absClassName ];
      }

      if ( \class_exists( $className ) )
      {
         $outClassName = $className;
         return true;
      }

      foreach ( $this->_resolveNamespaces as $namespace )
      {
         $testClass = "$namespace\\$className";
         if ( \class_exists( $testClass ) )
         {
            $outClassName = self::toAbsoluteClassName( $testClass );
            return true;
         }
      }

      return false;

   }

   /**
    * Returns formatted circular edge error, if circurlar edge found. Otherwise null
    *
    * @param string $className
    * @return string|null
    */
   protected function getCircularEdgeError( string $className ) : ?string
   {

      $className = self::toAbsoluteClassName( $className );

      if ( $circle = $this->findCircularEdge( $className ) )
      {
         return 'Found circular dependencies: ' . \implode( ' => ', $circle ) . " => $className";
      }

      return null;

   }

   /**
    * Finds circular dependencies and returns them
    *
    * @param string $className
    * @param array  $circle
    * @return array|null
    */
   protected function findCircularEdge( string $className, array $circle = [] ) : ?array
   {

      if ( ! isset( $this->_edges[ $className ] ) )
      {
         return null;
      }

      if ( \in_array( $className, $circle ) )
      {
         return $circle;
      }

      $circle[] = $className;

      foreach ( $this->_edges[ $className ] as $edge )
      {
         if ( $inner = $this->findCircularEdge( $edge, $circle ) )
         {
            return $inner;
         }
      }

      return null;

   }

   // </editor-fold>


   // <editor-fold desc="// -------   P R O T E C T E D   S T A T I C   M E T H O D S   ------------------">

   /**
    * Return the absolute class name.
    *
    * @param string $className
    * @return string
    */
   protected static function toAbsoluteClassName( string $className ) : string
   {

      return '\\' . \ltrim( $className, '\\' );

   }

   /**
    * Return the relative class name.
    *
    * @param string $className
    * @return string
    */
   protected static function toRelativeClassName( string $className ) : string
   {

      return \ltrim( $className, '\\' );

   }

   // </editor-fold>


}

