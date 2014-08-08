<?php
class CBVarPathStructField extends CBVarPathListItem
{
    /**
     * @var string
     */
    public $field;


    /**
     * @param string $field
     */
    public function __construct($field)
    {
        $this->field = $field;
    }
}
