<?php

/**
 * Class CBFunction
 * @property string $name
 * @TODO: eliminate CBFunction->name property, access name via getter/setter instead
 */
class CBFunction extends CBBlock implements ICBHasParentScope, ICBNamedEntity
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
     * @var int Braces mode (whether to force them or not): one of the CBBlock::BRACES constants.
     * Braces are mandatory for function code blocks (even for single-line functions).
     * @see CBBlock::$bracesMode
     */
    public $bracesMode = CBBlock::BRACES_MODE_ALWAYS;

    /**
     * @var CBFunctionParameter[]
     */
    protected $params = Array();


    /**
     * @param CBSettings $cbs
     * @param string $name Optional (can be set later): name of the function
     */
    public function __construct(CBSettings $cbs, $name = NULL)
    {
        parent::__construct($cbs);

        if ($name !== NULL AND ! is_string($name) AND ! $name instanceof CBString)
            $this->_cbs->error("\'{$name}\' is not a valid function name", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        $this->name = $name;
    }


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
                return parent::__set($name, $value);
                break;
        }
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
     * @param CBScope $scope
     * @see ICBHasParentScope::setParentScope()
     */
    public function setParentScope(CBScope $scope)
    {
        $this->_parentScope = $scope;
    }


    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        // If we are changing the name of this function - also make sure that the parent scope is updated
        $this->_name = $name;
        if ($this->getParentScope() !== NULL) {
            $this->getParentScope()->addFn($this);
        }
    }


    public function addParam(CBFunctionParameter $param)
    {
        if (! in_array($param, $this->params, TRUE)) {
            $this->params[] = $param;

            // Add parameter variable to function scope to prevent name clashes
            $this->addUndeclaredVar($param->getParamVar());
        }
    }

    /**
     * @return CBObject|null A value that represents $this. Only available in class instance methods.
     */
    public function getThis()
    {
        $cbClass = $this->getParentScope();
        if (FALSE == ($cbClass instanceof CBClass)) {
            $this->_cbs->error(
                "This function cannot use \$this, because it is not in a class"
                , __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
            return NULL;
        }

        $thisObj = new CBObject($this->_cbs, $cbClass);
        $thisObj->source = CBValueSource::factoryFromThis($this->_cbs);

        return $thisObj;
    }


    public function build(CBBackend $backend)
    {
        if (count($this->params) == 0) {
            $paramsCode = '';
        } else {
            $paramsCodeArr = Array();
            foreach ($this->params as $param) {
                $paramsCodeArr[] = $param->build($this, $backend);
            }
            $paramsCode = join(', ', $paramsCodeArr);
        }

        $code  = "function {$this->name}({$paramsCode})" . $this->_cbs->eol;
        $code .= parent::build($backend);

        return $code;
    }
}
