<?php
/**
 * Class CBClass
 * CBClass extends CBScope and not CBBlock, because a class can not have code in itself. It is only a container for
 * variables (properties) and functions (methods). Whereas a CBBlock should also contain a block of code.
 * @see CBBlock
 * @see CBScope
 * @property CBClass|CBClassRef|string|null $extends
 */
class CBClass extends CBScope implements ICBHasFunctions
{
    /**
     * @var CBString|string|null
     */
    public $name;

    /**
     * @var CBClass|CBClassRef|string|null The class that this class extends or NULL if it does not extend any class
     */
    protected $_extends;


    /**
     * @param CBSettings $cbs
     * @param string $name Optional (can be set later): name of the class
     */
    public function __construct(CBSettings $cbs, $name = NULL)
    {
        parent::__construct($cbs);

        if (! is_string($name) AND ! $name instanceof CBString)
            $this->_cbs->error("\'{$name}\' is not a valid class name", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        $this->name = $name;
    }


    public function __get($name)
    {
        switch ($name)
        {
            case 'extends':
                return $this->_extends;
                break;

            default:
                return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name)
        {
            case 'extends':
                if (! $value instanceof CBClass AND ! $value instanceof CBClassRef AND ! is_string($value))
                    $this->_cbs->error("\'{$value}\' is not extendable", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);
                else
                    $this->_extends = $value;

                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * @return CBObject A new Object instance of this class
     */
    public function newObject()
    {
        $obj = new CBObject($this->_cbs, $this);
        $obj->source = CBValueSource::factoryFromNewObject($this->_cbs, $obj, $this);
        return $obj;
    }

    /**
     * Builds a call to a static class method
     * @param string $name Name of the static method to call
     * @param array $params
     * @return CBFunctionCall
     */
    public function callFn($name, $params = Array())
    {
        $fn = $this->getFnByName($name);
        if ($fn === NULL)
            $this->_cbs->error("Trying to call a non-existing static function \'{$this->name}::{$name}()\'", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        return new CBFunctionCall($this->_cbs, $this, $fn, $params);
    }


    /**
     * @param CBBackend $backend
     * @return string
     */
    public function build(CBBackend $backend)
    {
        $code = 'class ' . $this->name . (!empty($this->_extends) ? " extends {$this->_extends}" : '') . $this->_cbs->eol . '{' . $this->_cbs->eol;

        $allVars = $this->getAllVars();
        $allFns = $this->getAllFns();

        foreach ($allVars as $var)
            $code .= $this->indent(1, 'var ' . $var->build($this, $backend)) . $this->_cbs->eol;

        if (!empty($allVars) AND !empty($allFns))
            $code .= $this->_cbs->eol . $this->_cbs->eol;

        $countFns = count($allFns);
        $fnsBuilt = 0;
        foreach ($allFns as $fn)
        {
            $code .= $this->indent(1, $fn->build($backend)) . $this->_cbs->eol;
            $fnsBuilt++;
            if ($fnsBuilt < $countFns) {
                $code .= $this->_cbs->eol;
            }
        }

        $code .= "}";

        return $code;
    }
}

