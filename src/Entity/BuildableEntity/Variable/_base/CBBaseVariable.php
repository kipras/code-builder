<?php

/**
 * Class CBBaseVariable
 * @property string $name
 */
abstract class CBBaseVariable extends CBBuildableEntity implements ICBHasParentScope, ICBNamedEntity
{
    /**
     * @var CBScope
     */
    private $_parentScope;

    /**
     * @var string
     */
    private $_name;

    /**
     * @var CBValue
     */
    private $_refVal;

    /**
     * @var bool If FALSE - this variable should not be declared in the Block that it is added to (e.g. function parameter)
     */
    public $isDeclared = TRUE;


    /**
     * @return CBType The type of the value that this CBVariable holds.
     * CodeBuilder language is statically, strongly typed. Which means that each variable has a specific type,
     * and that the type of a variable cannot change.
     */
    abstract public function getType();

    /**
     * @return CBValue
     */
    abstract public function getValue();

    /**
     * @return CBValue|null Initializer value or NULL if this variable is not initialized
     */
    abstract public function getInitializerValue();


    public function __get($name)
    {
        switch ($name)
        {
            case 'name':
                return $this->getName();
                break;

            default:
                return parent::__get($name);
                break;
        }
    }
    public function __set($name, $value)
    {
        switch ($name)
        {
            case 'name':
                $this->setName($value);
                break;

            default:
                parent::__set($name, $value);
                break;
        }
    }


    /**
     * For debugging purposes (e() uses this)
     * @return array
     */
    public function __toArray()
    {
        return Array(
            '_name' => $this->_name,
            '_refVal' => $this->_refVal,
        );
    }


    /**
     * @return CBScope
     * @see ICBHasParentScope::getParentScope()
     */
    public function getParentScope()
    {
        return $this->_parentScope;
    }

    /**
     * Sets parent scope of a variable and adds this variable to that scope
     * @param CBScope $scope
     * @see ICBHasParentScope::setParentScope()
     */
    public function setParentScope(CBScope $scope)
    {
        $this->_parentScope = $scope;
        $scope->addVar($this);
    }


    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        // If we are changing the name of this variable - also make sure that the parent scope is updated
        $this->_name = $name;
        if ($this->getParentScope() !== NULL) {
            $this->getParentScope()->addVar($this);
        }
    }


    /**
     * @return bool TRUE if an initializer is set for this variable, FALSE otherwise
     */
    public function isInitialized()
    {
        return $this->getInitializerValue() !== NULL;
    }


    /**
     * @return CBValue Returns a CBValue that refers to this CBBaseVariable
     */
    public function refVal()
    {
        if ($this->_refVal === NULL) {
            $value = $this->getValue();

            if ($value instanceof CBValue) {
                $class = get_class($value);
                $refVal = new $class($this->_cbs);
                $refVal->type = $value->type;
                $refVal->val = $value->val;
            } else {
                // @TODO: this should not be possible, CBVariable->_value should always hold a CBValue
                $refVal = new CBValue($this->_cbs);
            }
            $refVal->source = CBValueSource::factoryFromVar($this->_cbs, $this);
            $this->_refVal = $refVal;
        }

        return $this->_refVal;
    }


    /**
     * @param CBScope $scope The current scope, passed by the build() mechanism
     * @param CBBackend $backend
     * @param bool $buildAccessorOnly If this is set to TRUE - only returns the name of the variable. Otherwise
     * builds a variable declaration, with an initializer (if an initial value is set).
     * @return string
     */
    public function build(CBScope $scope, CBBackend $backend, $buildAccessorOnly = FALSE)
    {
        if ($this->name === NULL) {
            $this->_cbs->error("Trying to compile a variable, that is not given any name??", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
        }

        // Declaration
        if ($buildAccessorOnly) {
            $code = $backend->varName($this);
        } else {
            $code = $backend->buildVarDeclarationStatement($this, $scope);
        }

        return $code;
    }
}
