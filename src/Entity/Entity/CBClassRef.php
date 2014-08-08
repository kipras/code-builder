<?php
/**
 * Class CBClassRef
 * This CBEntity is used as a HLL AST element which references other classes, i.e. classes that are not defined via HLL
 */
class CBClassRef extends CBEntity implements ICBHasFunctions
{
    /**
     * @var string Name of the class being referenced
     */
    public $name;


    /**
     * @param CBSettings $cbs
     * @param string $name Name of the class being referenced
     */
    public function __construct(CBSettings $cbs, $name)
    {
        parent::__construct($cbs);

        $this->name = $name;
    }


    /**
     * Creates a new object of this class
     * @return CBObject
     */
    public function newObj()
    {
        $obj = new CBObject($this->_cbs, $this);
        $obj->source = CBValueSource::factoryFromNewObject($this->_cbs, $obj, $this);
        return $obj;
    }

    /**
     * Builds a call to a static class method
     * @param string $name The name of a static method in this class to call
     * @param array $params
     * @return CBFunctionCall
     */
    public function callFn($name, $params = Array())
    {
        return new CBFunctionCall($this->_cbs, $this, $name, $params);
    }
}
