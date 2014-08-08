<?php

/**
 * Class CBMutVar
 * CBMutVar is a variable (like a CBVariable), the difference is that a CBVariable is initialized to
 * a CBValue (which it holds as CBVariable->value) and is no longer modified, i.e. it is immutable.
 * Whereas a CBMutVar does not hold a final CBValue and is mutable.
 *
 * You should always prefer CBVariable instead of CBMutVar, as it is better for static analysis.
 * CBMutVar should only be used when you need mutability.
 *
 * @property CBValue $initialValue
 */
class CBMutVar extends CBBaseVariable
{
    /**
     * @var CBValue
     */
    private $_initialValue;


    /**
     * @return CBType
     */
    public function getType()
    {
        if ($this->_initialValue === NULL) {
            $this->_cbs->constructionError(
                "This ".$this->cbs->util->cb_get_class($this)." has no type information yet, because it is not yet fully built - "
                    ."initial value is not set. Call ->setInitialValue().");
            return $this->_cbs->factory->typeUnknown();
        }

        return $this->_initialValue->type;
    }

    public function getValue()
    {
        return $this->_initialValue;
    }

    public function getInitializerValue()
    {
        return $this->_initialValue;
    }


    /**
     * @return CBValue
     */
    public function getInitialValue()
    {
        return $this->_initialValue;
    }

    public function setInitialValue(CBValue $initialValue)
    {
        if ($this->_initialValue !== NULL) {
            $this->_cbs->constructionError(
                'Initial value of this '.$this->cbs->util->cb_get_class($this).' is already set, cannot change it');
            return NULL;
        }

        $this->_initialValue = $initialValue;
    }
}
