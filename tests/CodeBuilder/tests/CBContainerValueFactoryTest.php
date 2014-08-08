<?php


/**
* Class TestCodeBuilder
* This unit tests CodeBuilder
*/
class CBContainerValueFactoryTest extends CodeBuilderTestCase
{
    /**
     * @var CBContainerValueListFactory
     */
    private $listFact;

    /**
     * @var CBContainerValueStructFactory
     */
    private $structFact;

    /**
     * @var CBFactory
     */
    private $factory;


    public function __construct($label = false)
    {
        parent::__construct($label);

        $cbs = new CBSettingsDontShowErrors();
        $this->factory = $cbs->factory;
        $this->listFact = new CBContainerValueListFactory($cbs);
        $this->structFact = new CBContainerValueStructFactory($cbs);
    }


    public function test_list_plain()
    {
        $type = $this->factory->typeList($this->factory->typeInt());
        $input = Array(1, 2, 3);
        $res = $this->listFact->factoryContainerValueFromPlainValues($input, $type);

        $expected = $this->factory->newListValue($type);
        $expected->val = Array(
            $this->factory->atomicValue(1, $this->factory->typeInt()),
            $this->factory->atomicValue(2, $this->factory->typeInt()),
            $this->factory->atomicValue(3, $this->factory->typeInt()),
        );
        $this->assertArraysIdentical($expected, $res);
    }

    /**
     * A NULL value is a valid value for all types
     */
    public function test_list_plain_contains_null_value()
    {
        $type = $this->factory->typeList($this->factory->typeInt());
        $input = Array(1, NULL, 3);
        $res = $this->listFact->factoryContainerValueFromPlainValues($input, $type);

        $expected = $this->factory->newListValue($type);
        $expected->val = Array(
            $this->factory->atomicValue(1, $this->factory->typeInt()),
            $this->factory->atomicValue(NULL, $this->factory->typeInt()),
            $this->factory->atomicValue(3, $this->factory->typeInt()),
        );
        $this->assertArraysIdentical($expected, $res);

    }

    public function test_list_cbvalues()
    {
        $type = $this->factory->typeList($this->factory->typeInt());
        $input = Array(
            $this->factory->atomicValue(1, $this->factory->typeInt()),
            $this->factory->atomicValue(2, $this->factory->typeInt()),
            $this->factory->atomicValue(3, $this->factory->typeInt()),
        );
        $res = $this->listFact->factoryContainerValueFromCBValues($input, $type);

        $expected = $this->factory->newListValue($type);
        $expected->val = Array(
            $this->factory->atomicValue(1, $this->factory->typeInt()),
            $this->factory->atomicValue(2, $this->factory->typeInt()),
            $this->factory->atomicValue(3, $this->factory->typeInt()),
        );
        $this->assertArraysIdentical($expected, $res);
    }

    public function test_list_cbvalues_wrong_type()
    {
        $type = $this->factory->typeList($this->factory->typeInt());
        $input = Array(
            $this->factory->atomicValue('foo', $this->factory->typeString()),
            $this->factory->atomicValue('bar', $this->factory->typeString()),
        );
        $res = $this->listFact->factoryContainerValueFromCBValues($input, $type);
        $this->assertNull($res);
    }

    public function test_struct_plain()
    {
        $type = $this->factory->typeStruct(Array(
                'int' => $this->factory->typeInt(),
            ));
        $input = Array(
            'int' => 1,
        );
        $res = $this->structFact->factoryContainerValueFromPlainValues($input, $type);

        $expected = $this->factory->newStructValue($type);
        $expected->val = Array(
            'int' => $this->factory->atomicValue(1, $this->factory->typeInt()),
        );
        $this->assertArraysIdentical($expected, $res);
    }

    /**
     * A NULL value is a valid value for all types
     */
    public function test_struct_plain_contains_null_value()
    {
        $type = $this->factory->typeStruct(Array(
                'int' => $this->factory->typeInt(),
            ));
        $input = Array(
            'int' => NULL,
        );
        $res = $this->structFact->factoryContainerValueFromPlainValues($input, $type);

        $expected = $this->factory->newStructValue($type);
        $expected->val = Array(
            'int' => $this->factory->atomicValue(NULL, $this->factory->typeInt()),
        );
        $this->assertArraysIdentical($expected, $res);
    }

    public function test_struct_plain_too_many_keys()
    {
        $type = $this->factory->typeStruct(Array(
                'int' => $this->factory->typeInt(),
            ));
        $input = Array(
            'int' => 1,
            'foo' => 'bar',
        );
        $res = $this->structFact->factoryContainerValueFromPlainValues($input, $type);
        $this->assertNull($res);
    }

    public function test_struct_plain_missing_key()
    {
        $type = $this->factory->typeStruct(Array(
                'int' => $this->factory->typeInt(),
            ));
        $input = Array();
        $res = $this->structFact->factoryContainerValueFromPlainValues($input, $type);
        $this->assertNull($res);
    }

    public function test_struct_cbvalues()
    {
        $type = $this->factory->typeStruct(Array(
                'int' => $this->factory->typeInt(),
            ));
        $input = Array(
            'int' => $this->factory->atomicValue(1, $this->factory->typeInt()),
        );
        $res = $this->structFact->factoryContainerValueFromCBValues($input, $type);

        $expected = $this->factory->newStructValue($type);
        $expected->val = Array(
            'int' => $this->factory->atomicValue(1, $this->factory->typeInt()),
        );
        $this->assertArraysIdentical($expected, $res);
    }


    public function test_nested_list_plain()
    {
        $innerType = $this->factory->typeList($this->factory->typeInt());
        $type = $this->factory->typeList($innerType);
        $input = Array(
            Array(1, 2),
            Array(3, 4),
        );
        $res = $this->listFact->factoryContainerValueFromPlainValues($input, $type);

        $innerVal = Array(
            $this->factory->newListValue($innerType),
            $this->factory->newListValue($innerType),
        );
        $innerVal[0]->val = Array(
            $this->factory->atomicValue(1, $this->factory->typeInt()),
            $this->factory->atomicValue(2, $this->factory->typeInt()),
        );
        $innerVal[1]->val = Array(
            $this->factory->atomicValue(3, $this->factory->typeInt()),
            $this->factory->atomicValue(4, $this->factory->typeInt()),
        );

        $expected = $this->factory->newListValue($type);
        $expected->val = $innerVal;
        $this->assertArraysIdentical($expected, $res);
    }

    /**
     * Tests to make sure an error is thrown when trying to factory a nested CBList from plain values when those
     * plain values contain CBValues.
     */
    public function test_nested_list_plain_where_inner_values_are_cbvalues()
    {
        $type = $this->test_nested_list_cbvalues__type();
        $input = $this->test_nested_list_cbvalues__input();
        $res = $this->listFact->factoryContainerValueFromPlainValues($input, $type);
        $this->assertNull($res);
    }

    public function test_nested_list_plain_where_nested_values_are_cbvalues()
    {
        $type = $this->test_nested_list_cbvalues__type();
        $input = $this->test_nested_list_cbvalues__input();
        $input = Array(
            $input[0]->val,
            $input[1]->val,
        );
        $res = $this->listFact->factoryContainerValueFromPlainValues($input, $type);
        $this->assertNull($res);
    }

    public function test_nested_list_cbvalues()
    {
        $type = $this->test_nested_list_cbvalues__type();
        $input = $this->test_nested_list_cbvalues__input();
        $res = $this->listFact->factoryContainerValueFromCBValues($input, $type);

        $expected = $this->factory->newListValue($type);
        $expected->val = $input;
        $this->assertArraysIdentical($expected, $res);
    }

    private function test_nested_list_cbvalues__type()
    {
        $innerType = $this->factory->typeList($this->factory->typeInt());
        return $this->factory->typeList($innerType);
    }

    private function test_nested_list_cbvalues__input()
    {
        $type = $this->test_nested_list_cbvalues__type();
        $innerVal = Array(
            $this->factory->newListValue($type->itemType),
            $this->factory->newListValue($type->itemType),
        );
        $innerVal[0]->val = Array(
            $this->factory->atomicValue(1, $this->factory->typeInt()),
            $this->factory->atomicValue(2, $this->factory->typeInt()),
        );
        $innerVal[1]->val = Array(
            $this->factory->atomicValue(3, $this->factory->typeInt()),
            $this->factory->atomicValue(4, $this->factory->typeInt()),
        );

        return $innerVal;
    }

    public function test_nested_struct_plain()
    {
        $intStructType = $this->factory->typeStruct(Array(
                'innerInt' => $this->factory->typeInt(),
            ));
        $intListType = $this->factory->typeList(
            $this->factory->typeInt()
        );
        $type = $this->factory->typeStruct(Array(
                'int' => $this->factory->typeInt(),
                'intStruct' => $intStructType,
                'intList' => $intListType,
            ));
        $input = Array(
            'int' => 1,
            'intStruct' => Array(
                'innerInt' => 2,
            ),
            'intList' => Array(3, 4),
        );
        $res = $this->structFact->factoryContainerValueFromPlainValues($input, $type);

        $intVal = $this->factory->atomicValue(1, $this->factory->typeInt());
        $intStructVal = $this->factory->newStructValue($intStructType);
        $intStructVal->val = Array(
            'innerInt' => $this->factory->atomicValue(2, $this->factory->typeInt()),
        );
        $intListVal = $this->factory->newListValue($intListType);
        $intListVal->val = Array(
            $this->factory->atomicValue(3, $this->factory->typeInt()),
            $this->factory->atomicValue(4, $this->factory->typeInt()),
        );

        $expected = $this->factory->newStructValue($type);
        $expected->val = Array(
            'int' => $intVal,
            'intStruct' => $intStructVal,
            'intList' => $intListVal,
        );
        $this->assertArraysIdentical($expected, $res);
    }

    /**
     * Tests to make sure an error is thrown when trying to factory a nested CBList from plain values when those
     * plain values contain CBValues.
     */
    public function test_nested_struct_plain_where_inner_values_are_cbvalues()
    {
        $type = $this->test_nested_struct_cbvalues__type();
        $input = $this->test_nested_struct_cbvalues__input();
        $res = $this->structFact->factoryContainerValueFromPlainValues($input, $type);
        $this->assertNull($res);
    }

    public function test_nested_struct_plain_where_nested_values_are_cbvalues()
    {
        $type = $this->test_nested_struct_cbvalues__type();
        $input = $this->test_nested_struct_cbvalues__input();
        $input = Array(
            'int' => $input['int']->val,
            'intStruct' => $input['intStruct']->val,
            'intList' => $input['intList']->val,
        );
        $res = $this->structFact->factoryContainerValueFromPlainValues($input, $type);
        $this->assertNull($res);
    }

    public function test_nested_struct_cbvalues()
    {
        $type = $this->test_nested_struct_cbvalues__type();
        $input = $this->test_nested_struct_cbvalues__input();
        $res = $this->structFact->factoryContainerValueFromCBValues($input, $type);

        $expected = $this->factory->newStructValue($type);
        $expected->val = $input;
        $this->assertArraysIdentical($expected, $res);
    }

    private function test_nested_struct_cbvalues__type()
    {
        $intStructType = $this->factory->typeStruct(Array(
                'innerInt' => $this->factory->typeInt(),
            ));
        $intListType = $this->factory->typeList(
            $this->factory->typeInt()
        );
        return $this->factory->typeStruct(Array(
                'int' => $this->factory->typeInt(),
                'intStruct' => $intStructType,
                'intList' => $intListType,
            ));
    }

    private function test_nested_struct_cbvalues__input()
    {
        $type = $this->test_nested_struct_cbvalues__type();

        $intVal = $this->factory->atomicValue(1, $type->fieldTypes['int']);
        $intStructVal = $this->factory->newStructValue($type->fieldTypes['intStruct']);
        $intStructVal->val = Array(
            'innerInt' => $this->factory->atomicValue(2, $this->factory->typeInt()),
        );
        $intListVal = $this->factory->newListValue($type->fieldTypes['intList']);
        $intListVal->val = Array(
            $this->factory->atomicValue(3, $this->factory->typeInt()),
            $this->factory->atomicValue(4, $this->factory->typeInt()),
        );

        return Array(
            'int' => $intVal,
            'intStruct' => $intStructVal,
            'intList' => $intListVal,
        );
    }
}
