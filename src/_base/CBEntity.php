<?php
/**
 * Class CBEntity
 */
abstract class CBEntity
{
    /**
     * @var CBSettings
     */
    protected $_cbs;

    /**
     * @var string[]|CBFile[]|CBClass[]|CBFunction[] A list of unique dependencies
     */
    protected $_dependencies = Array();


    /**
     * @param CBSettings $cbs
     */
    public function __construct(CBSettings $cbs)
    {
        $this->_cbs = $cbs;

        $this->init();
    }


    /**#@+
     * Empty __get()/__set(), necessary for extending classes
     */
    public function __get($name)
    {
        $this->_cbs->warning("No such property '{$name}' exists in " . get_class($this), "Property not found");
        return NULL;
    }
    public function __set($name, $value)
    {
        $this->_cbs->warning("No such property '{$name}' exists in " . get_class($this), "Property not found");
    }
    /**#@-*/


    /**
     * Any necessary non-parameterized initialization for an object should be implemented in this method,
     * instead of in the constructor, so changes to constructor implementation are made in as few
     * CodeBuilder classes as possible.
     * So that when parameters of constructor need to be changed, we could do it more easily.
     *
     * This method intentionally takes no parameters. Parametrized initialization should be done in constructor.
     */
    protected function init()
    {
    }


    /**
     * Adds a dependency. $dep may be a string (filename of an existing dependency)
     * or a CBFile/CBClass/CBFunction
     * @param $dep string|CBFile|CBClass|CBFunction A dependency
     */
    public function addDependency($dep)
    {
        if (is_string($dep) OR $dep instanceof CBFile
         OR $dep instanceof CBClass OR $dep instanceof CBFunction)
        {
            // Do not allow duplicate dependencies in the dependency list
            if (FALSE == in_array($dep, $this->_dependencies, TRUE))
                $this->_dependencies[] = $dep;
        }
        else
            $this->_cbs->warning("\'{$dep}\' is not a valid dependency", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
    }

    /**
     * @return CBClass[]|CBFile[]|CBFunction[]|string[]
     */
    public function getDependencies()
    {
        return $this->_dependencies;
    }


    /**
     * @param int $amount Number of tabs to indent
     * @param string|bool $code Code (as string)
     * @return string Indented code
     * @deprecated Should use CBSettings->indent() instead
     */
    public function indent($amount, $code = FALSE)
    {
        return $this->_cbs->indent($amount, $code);
    }


    // ------------------------------------------------------------------------
    // HELPER FUNCTIONS


    /**
     * @return bool TRUE if this entity is buildable (has the build() method).
     * NOTE that the build() method for all entities except the descendants of CBBlock require a CBScope $scope
     * parameter to be passed. CBBlock does not require it because it itself is the scope.
     * @see CBBuildableEntity::build()
     * @see CBBlock::build()
     */
    public function isBuildable()
    {
        return ($this instanceof CBBuildableEntity OR $this instanceof CBBlock);
    }

    /**
     * Compiles the value constant for the given value in the given scope
     * @param CBValue|CBVariable|CBFunctionCallResult|array|int|float|string|bool|null $val
     * @param CBScope $scope
     * @param CBBackend $backend
     * @return int|null|string
     * @deprecated Should use CBBackend->buildVal() instead
     */
    public function val($val, CBScope $scope, CBBackend $backend)
    {
        return $backend->buildVal($val, $scope);
    }
}
