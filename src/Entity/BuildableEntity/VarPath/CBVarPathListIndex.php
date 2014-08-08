<?php
class CBVarPathListIndex
{
    /**
     * @var CBValue|int
     */
    public $index;


    /**
     * @param CBValue|int $index
     */
    public function __construct($index)
    {
        $this->index = $index;
    }
}
