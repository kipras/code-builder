<?php
class CBObject extends CBValue implements ICBHasFunctions
{
    /**
     * @var CBClass|CBClassRef|string The class of this object
     */
    public $class;

    /**
     * @var CBVariable[] Dynamically assigned object properties
     */
    private $dynamicProps = Array();


    /**
     * @param CBSettings $cbs
     * @param CBClass|CBClassRef|string $class
     */
    public function __construct(CBSettings $cbs, $class = NULL)
    {
        parent::__construct($cbs);

        if (! $this->_validClass($class))
            $this->_cbs->error("\'{$class}\' is not a valid class", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        $this->type = $cbs->factory->typeObject();
        $this->class = $class;
    }

    /**
     * @param CBClass|CBClassRef|string $class
     * @return bool
     */
    public function _validClass($class)
    {
        return (($class === NULL OR is_string($class) OR $class instanceof CBClass OR $class instanceof CBClassRef) ? TRUE : FALSE);
    }


    /**
     * @param $name
     * @return CBVariable Object property
     */
    public function getDynamicProp($name)
    {
        $prop = new CBVariable($this->_cbs, $name);
        $prop->parentObject = $this;

        $this->dynamicProps[] = $prop;

        return $prop;
    }

    /**
     * @return CBVariable[] All dynamically assigned object properties
     */
    public function getAllDynamicProps()
    {
        return $this->dynamicProps;
    }

    /**
     * @param string $name
     * @param array $params
     * @return CBFunctionCall
     */
    public function callFn($name, $params = Array())
    {
        return new CBFunctionCall($this->_cbs, $this, $name, $params);
    }


    public function buildObjectInitializer()
    {
        if (! $this->_validClass($this->class))
            $this->_cbs->error("\'{$this->class}\' is not a valid class", __CLASS__ . '->' . __FUNCTION__ . '():' . __LINE__);

        // If this is an empty object value
        if ($this->class === NULL)
            $className = 'StdClass';
        else if (is_string($this->class))
            $className = $this->class;
        else if ($this->class instanceof CBClass)
            $className = $this->class->name;
        else if ($this->class instanceof CBClassRef)
            $className = $this->class->name;

        return "new {$className}()";
    }
}
