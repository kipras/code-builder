<?php
class CBFunctionCallResult extends CBEntity
{
    /**
     * @var CBFunctionCall
     */
    public $fnCall;


    public function __construct(CBSettings $cbs, CBFunctionCall $fnCall)
    {
        parent::__construct($cbs);

        $this->fnCall = $fnCall;
    }


    /**
     * @return CBValue
     */
    public function value()
    {
        $value = new CBValue($this->_cbs);
        $value->source = CBValueSource::factoryFromFnCallRes($this->_cbs, $this);
        return $value;
    }

    /**
     * @return CBObject
     * @deprecated Should use ->value()->toObject() instead
     */
    public function toObject()
    {
        $r = new CBObject($this->_cbs);
        $r->source = CBValueSource::factoryFromFnCallRes($this->_cbs, $this);

        return $r;
    }

    /**
     * @return CBVariable
     */
    public function assignToNewVar()
    {
        $var = new CBVariable($this->_cbs);
        $var->value = $this->value();

        return $var;
    }
}
