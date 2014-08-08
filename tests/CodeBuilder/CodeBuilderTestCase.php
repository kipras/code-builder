<?php

class CodeBuilderTestCase extends UnitTestCase
{
    protected function assertArraysIdentical($expected, $result, $message = '%s')
    {
        return $this->assertTrue(arrays_identical($expected, $result), $message);
    }
}
