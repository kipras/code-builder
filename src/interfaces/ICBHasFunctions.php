<?php
/**
 * Class ICBHasFunctions
 * Interface for CodeBuilder entities that have functions
 */
interface ICBHasFunctions
{
    /**
     * @param string $name
     * @param array $params
     * @return CBFunctionCall
     */
    public function callFn($name, $params = Array());
}
