<?php


/**
 * Class TestCodeBuilder
 * This unit tests CodeBuilder
 */
class TestCodeBuilder extends CodeBuilderTestCase
{
    /**
     * @var CBSettings
     */
    private $cbs;

    /**
     * @var CBSettings
     */
    private $cbsDontShowErrors;

    /**
     * @var CBFactory
     */
    private $factory;

    /**
     * @var CBFactory
     */
    private $factoryDontShowErrors;

    /**
     * @var CBPhpBackend
     */
    private $phpBackend;

    /**
     * @var CBCBackend
     */
    private $cBackend;


    public function __construct($label = false)
    {
        parent::__construct($label);

        $this->cbs = new CBSettings();
        $this->factory = $this->cbs->factory;
        $this->phpBackend = new CBPhpBackend($this->cbs);
        $this->cBackend = new CBCBackend($this->cbs);

        $this->cbsDontShowErrors = new CBSettingsDontShowErrors();
        $this->factoryDontShowErrors = $this->cbsDontShowErrors->factory;
    }


    protected function prepareResultArray($array)
    {
        if (is_object($array)) {
            $obj = $array;
            $array = get_object_vars($obj);
            $array = [
                    '__class' => get_class($obj),
                ] + $array;
        }
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                $array[$k] = $this->prepareResultArray($v);
            }
        }
        return $array;
    }

    protected function assertArraysIdentical($expected, $result, $message = '%s')
    {
        $expected = $this->prepareResultArray($expected);
        $result = $this->prepareResultArray($result);
        $identical = arrays_identical($expected, $result);
        $r = $this->assertTrue(arrays_identical($expected, $result), $message);
        if (FALSE == $identical) {
            echo ArrayDiffHtml::diff($expected, $result);
        }

        return $r;
    }


    public function testCodeBuilder1()
    {
        $file = new CBFile($this->cbs);

        $testClass = new CBClass($this->cbs, 'TestClass');
        $file->addClass($testClass);

        $member = new CBVariable($this->cbs);
        $testClass->addVar($member);
        $member->value = CBValue::factoryFromValue($this->cbs, 'abc');

        $method1 = new CBFunction($this->cbs, 'method1');
        $testClass->addFn($method1);
        $method1->return = $member;

        $res = $file->build($this->phpBackend);
        $expected = '<?php

class TestClass
{
    var $tmp1 = \'abc\';


    function method1()
    {
        return $this->tmp1;
    }
}
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);

        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function testCodeBuilder2()
    {
        $file = new CBFile($this->cbs);

        $testClass = new CBClass($this->cbs, 'TestClass');
        $file->addClass($testClass);
        $testClass->extends = 'SomeOtherClass';

        $method1 = new CBFunction($this->cbs, 'method1');
        $testClass->addFn($method1);

        $ObjectTemplateClassRef = new CBClassRef($this->cbs, 'ObjectTemplate');
        $templateVar = $ObjectTemplateClassRef->newObj()->assignToNewVar();
        $templateVar->name = 'template';
        $method1->addVar($templateVar);

        $GETVar = new CBVariable($this->cbs);
        $method1->addVar($GETVar);
        $GETVar->name = '_GET';
        $GETVar->superGlobal = TRUE;

        $scopeVar = new CBVariable($this->cbs);
        $method1->addVar($scopeVar);
        $scopeVar->name = 'scope';
        $scopeVar->value = CBStruct::factoryFromValueMap($this->cbs, Array(
                '$get' => $GETVar->refVal(),
            ));

        $collectionItemClassRef = new CBClassRef($this->cbs, 'CollectionItem');
        $tplAssignCall = $templateVar->refVal()->callFn('assign', Array(
            '$someCollection',
            $collectionItemClassRef
                ->callFn('create')
                ->res()
                ->toObject()
                ->callFn('getList', Array(
                    NULL,
                    Array(
                        'collectionId' => 1,
                    ),
                ))
                ->res(),
        ));
        $method1->addFnCall($tplAssignCall);

        // And also, add some dependencies to the file and test dependency adding in inner scopes
        $file->addDependency('classes/SomeOtherClass.class.php');
        $testClass->addDependency('classes/OneMoreClass.class.php');
        $method1->addDependency('classes/OneMoreClass.class.php');
        $method1->addDependency('classes/Additional.class.php');


        $res = $file->build($this->phpBackend);
        $expected = '<?php

require_once \'classes/SomeOtherClass.class.php\';
require_once \'classes/OneMoreClass.class.php\';
require_once \'classes/Additional.class.php\';


class TestClass extends SomeOtherClass
{
    function method1()
    {
        $template = new ObjectTemplate();
        $scope = Array(
            \'$get\' => $_GET,
        );

        $template->assign(
            \'$someCollection\'
          , CollectionItem::create()->getList(
                NULL
              , Array(
                    \'collectionId\' => 1,
                )
            )
        );
    }
}
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);

        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    /**
     * Test putting PHP logic directly in a file (instead of generating classes and/or functions)
     */
    public function testFile()
    {
        $file = new CBFile($this->cbs);

        $member = new CBVariable($this->cbs);
        $file->addVar($member);
        $member->value = CBValue::factoryFromValue($this->cbs, 'abc');

        $functionCall = new CBFunctionCall($this->cbs, NULL, 'someFunction', Array(1, 'test'));
        $file->addFnCall($functionCall);

        $res = $file->build($this->phpBackend);
        $expected = '<?php

$tmp1 = \'abc\';

someFunction(
    1
  , \'test\'
);
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);

        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function test_function_parameters()
    {
        $file = new CBFile($this->cbs);

        $function = new CBFunction($this->cbs, 'someFunction');
        $file->addFn($function);

        $readonlyParam = new CBFunctionParameter($this->cbs);
        $function->addParam($readonlyParam);
        $readonlyParam->getParamVar()->name = 'readonlyParam';

        $writableParam = new CBFunctionParameter($this->cbs);
        $function->addParam($writableParam);
        $writableParam->getParamVar()->name = 'writableParam';
        $writableParam->getParamVar()->value = CBValue::factoryFromValue($this->cbs, 1);
        $writableParam->writable = TRUE;

        $function->return = $readonlyParam->getParamVar()->refVal();

        $res = $file->build($this->phpBackend);
        $expected = '<?php

function someFunction($readonlyParam, &$writableParam)
{
    $writableParam = 1;

    return $readonlyParam;
}
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);

        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function testVariableNameCollisions()
    {
        $file = new CBFile($this->cbs);

        $member1 = new CBVariable($this->cbs);
        $member1->name = 'test';
        $file->addVar($member1);
        $member1->value = CBValue::factoryFromValue($this->cbs, 'abc');

        $member2 = new CBVariable($this->cbs);
        $member2->name = 'test';
        $file->addVar($member2);
        $member2->value = CBValue::factoryFromValue($this->cbs, 'def');

        $res = $file->build($this->phpBackend);
        $expected = '<?php

$test = \'abc\';
$test1 = \'def\';
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);

        $this->assertEqual($expected, $res);


        // Test renaming variables, to colliding names, after they are added to the function scope
        $file = new CBFile($this->cbs);

        $member1 = new CBVariable($this->cbs);
        $file->addVar($member1);
        $member1->name = 'test';
        $member1->value = CBValue::factoryFromValue($this->cbs, 'abc');

        $member2 = new CBVariable($this->cbs);
        $file->addVar($member2);
        $member2->name = 'test';
        $member2->value = CBValue::factoryFromValue($this->cbs, 'def');

        $res = $file->build($this->phpBackend);
        $expected = '<?php

$test = \'abc\';
$test1 = \'def\';
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);

        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function testReturn()
    {
        // Test returning a function result
        $file = new CBFile($this->cbs);
        $function = new CBFunction($this->cbs, 'fn1');
        $file->addFn($function);

        $fnCall = new CBFunctionCall($this->cbs, NULL, 'someFunction');
        $function->return = $fnCall->res();

        $res = $file->build($this->phpBackend);
        $expected = '<?php

function fn1()
{
    return someFunction();
}
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);

        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function testEach()
    {
        $file = new CBFile($this->cbs);
        $function = new CBFunction($this->cbs, 'fn1');
        $file->addFn($function);

        $list = Array(
            'a' => 'b',
            'c' => 1.5,
            'd' => 3,
            Array(
                'e' => 'test',
            ),
        );
        $listVar = CBValue::factoryFromValue($this->cbs, $list)->assignToNewVar();
        $function->addVar($listVar);

        $each = new CBEach($this->cbs, $listVar);
        $function->addBlock($each);
        $each->key = new CBVariable($this->cbs);

        $someClassRef = new CBClassRef($this->cbs, 'SomeClass');
        $someVar = $someClassRef->newObj()->assignToNewVar();
        $function->addVar($someVar);
        $someVarFunctionCall = $someVar->refVal()->callFn('someFunction', Array(
            $each->item,
        ));
        $each->addFnCall($someVarFunctionCall);

        $res = $file->build($this->phpBackend);
        $expected = '<?php

function fn1()
{
    $tmp1 = Array(
        \'a\' => \'b\',
        \'c\' => 1.5,
        \'d\' => 3,
        0 => Array(
            \'e\' => \'test\',
        ),
    );
    // $tmp2;
    // $tmp3;
    $tmp4 = new SomeClass();

    foreach ($tmp1 as $tmp3 => $tmp2)
        $tmp4->someFunction($tmp2);
}
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);

        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    /**
     * Test to make sure variables can are correctly declared in the scope to which they are added,
     * but that naming and naming collision checking is done in first container naming scope (CBFile, CBFunction),
     * if this scope is not a naming scope
     * CodeBuilder behaves this way to make it easier to generate backend PHP and C code, both of which don't have
     * block scoping and instead have function scoping, so variable names have to be unique per function, not
     * only per block.
     *
     * 'tmp' named vars check automatic naming
     * 'foo' named vars check assigned name collision detection & fixing
     */
    public function testScoping()
    {
        $file = $this->factory->file();
        $function = $this->factory->fn();
        $file->addFn($function);

        //  tmp1 = 1
        $tmp1Var = $this->factory->atomicValue(1, $this->factory->typeInt())->assignToNewVar();
        $function->addVar($tmp1Var);

        //  foo = 'foo'
        $fooVar = $this->factory->atomicValue('foo', $this->factory->typeString())->assignToNewVar();
        $function->addVar($fooVar);
        $fooVar->name = 'foo';

        //  {...}
        $innerBlock = $this->factory->block();
        $innerBlock->bracesMode = CBBlock::BRACES_MODE_ALWAYS;
        $function->addBlock($innerBlock);

        //      tmp2 = 2
        $tmp2Var = $this->factory->atomicValue(2, $this->factory->typeInt())->assignToNewVar();
        $innerBlock->addVar($tmp2Var);

        //      foo1 = 'foo1'
        $foo1Var = $this->factory->atomicValue('foo1', $this->factory->typeString())->assignToNewVar();
        $innerBlock->addVar($foo1Var);
        $foo1Var->name = 'foo';

        //      {...}
        $innerSubBlock = $this->factory->block();
        $innerSubBlock->bracesMode = CBBlock::BRACES_MODE_ALWAYS;
        $innerBlock->addBlock($innerSubBlock);

        //          tmp3 = 2
        $tmp3Var = $this->factory->atomicValue(3, $this->factory->typeInt())->assignToNewVar();
        $innerSubBlock->addVar($tmp3Var);

        //          foo2 = 'foo1'
        $foo2Var = $this->factory->atomicValue('foo2', $this->factory->typeString())->assignToNewVar();
        $innerSubBlock->addVar($foo2Var);
        $foo2Var->name = 'foo';


        $res = $file->build($this->phpBackend);
        $expected = '<?php

function tmp1()
{
    $tmp1 = 1;
    $foo = \'foo\';
    // $tmp2;
    // $foo1;
    // $tmp3;
    // $foo2;

    {
        $tmp2 = 2;
        $foo1 = \'foo1\';

        {
            $tmp3 = 3;
            $foo2 = \'foo2\';
        }
    }
}
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    /**
     * Same as testScoping() but builds blocks in reverse, i.e. builds everything starting with the inner most
     * block and then builds its parent block, etc. and finishes by adding the top level block to the naming
     * scope (CBFunction).
     * This checks if naming works correctly even when everything is added to the naming scope at the very last,
     * right before code generation.
     */
    public function testScoping_reverseBuilding()
    {
        //          {...}
        $innerSubBlock = $this->factory->block();
        $innerSubBlock->bracesMode = CBBlock::BRACES_MODE_ALWAYS;

        //              tmp3 = 2
        $tmp3Var = $this->factory->atomicValue(3, $this->factory->typeInt())->assignToNewVar();
        $innerSubBlock->addVar($tmp3Var);

        //              foo2 = 'foo1'
        $foo2Var = $this->factory->atomicValue('foo2', $this->factory->typeString())->assignToNewVar();
        $innerSubBlock->addVar($foo2Var);
        $foo2Var->name = 'foo';


        //      {...}
        $innerBlock = $this->factory->block();
        $innerBlock->bracesMode = CBBlock::BRACES_MODE_ALWAYS;

        //          tmp2 = 2
        $tmp2Var = $this->factory->atomicValue(2, $this->factory->typeInt())->assignToNewVar();
        $innerBlock->addVar($tmp2Var);

        $innerBlock->addBlock($innerSubBlock);

        //          foo1 = 'foo1'
        $foo1Var = $this->factory->atomicValue('foo1', $this->factory->typeString())->assignToNewVar();
        $innerBlock->addVar($foo1Var);
        $foo1Var->name = 'foo';


        //      tmp1 = 1
        $tmp1Var = $this->factory->atomicValue(1, $this->factory->typeInt())->assignToNewVar();

        //      foo = 'foo'
        $fooVar = $this->factory->atomicValue('foo', $this->factory->typeString())->assignToNewVar();
        $fooVar->name = 'foo';


        // function tmp1()
        $file = $this->factory->file();
        $function = $this->factory->fn();
        $file->addFn($function);

        $function->addVar($tmp1Var);
        $function->addVar($fooVar);
        $function->addBlock($innerBlock);


        $res = $file->build($this->phpBackend);
        $expected = '<?php

function tmp1()
{
    $tmp1 = 1;
    $foo = \'foo\';
    // $tmp2;
    // $foo1;
    // $tmp3;
    // $foo2;

    {
        $tmp2 = 2;
        $foo1 = \'foo1\';

        {
            $tmp3 = 3;
            $foo2 = \'foo2\';
        }
    }
}
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    /**
     * Test to make sure any values that reference a variable (i.e. that are taken from a variable using ->refVal())
     * actually reference that variable when their value is built, instead of directly using the value inside
     * that variable.
     * This feature is necessary, because we are compiling to imperative languages and we sometimes need support
     * for mutable variables, e.g. when interfacing with language or library APIs or foreign code.
     * Or when doing loops.
     *
     * This gives CodeBuilder flexibility - it can support mutability. But it can also be pure - for that
     * instead of using CBVariable->refVal() you would just use CBVariable->value.
     * The programmer using the library can choose which one he wants, based on the situation.
     * We don't force him to do one or the other.
     */
    public function test_refVal()
    {
        $file = $this->factory->file();

        $sourceType = $this->factory->typeStruct(Array(
                'foo' => $this->factory->typeString(),
            ));
        $sourceValue = $this->factory->containerValueFromPlainValues(Array(
                'foo' => 'bar',
            ), $sourceType);
        $sourceVar = $sourceValue->assignToNewVar();
        $file->addVar($sourceVar);
        $sourceVar->name = 'source';

        // Test to make sure $result is not directly assigned the 'bar' value, but goes through
        // $source to get it
        $resultVar = $sourceVar->refVal()->toStruct()->getValueForKey('foo')->assignToNewVar();
        $file->addVar($resultVar);
        $resultVar->name = 'result';

        $res = $file->build($this->phpBackend);
        $expected = '<?php

$source = Array(
    \'foo\' => \'bar\',
);
$result = $source[\'foo\'];
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }


    private function _test_path_parser_var()
    {
        $sourceType = $this->factory->typeList(
            $this->factory->typeStruct(Array(
                    'inner' => $this->factory->typeList(
                            $this->factory->typeStruct(Array(
                                    'int' => $this->factory->typeInt(),
                                ))),
                ))
        );
        $source = Array(
            Array(
                'inner' => Array(
                    Array(
                        'int' => 1,
                    ),
                    Array(
                        'int' => 2,
                    ),
                ),
            ),
            Array(
                'inner' => Array(),
            ),
            Array(
                'inner' => Array(
                    Array(
                        'int' => 3,
                    ),
                ),
            ),
        );
        $sourceVar = $this->factory->containerValueFromPlainValues($source, $sourceType)->assignToNewVar();

        return $sourceVar;
    }

    private function _test_path_parser($path, $expectedPathTokenList)
    {
        $selectorPathParser = $this->factory->selectorPathParser();
        $res = $selectorPathParser->parse($path);
        $this->assertArraysIdentical($expectedPathTokenList, $res);
    }

    public function test_path_parser_1()
    {
        $this->_test_path_parser('[]', Array(
                Array(
                    '__class' => 'CBSelectorTokenList',
                ),
            ));
    }

    public function test_path_parser_2()
    {
        $this->_test_path_parser('[].inner', Array(
                Array(
                    '__class' => 'CBSelectorTokenList',
                ),
                Array(
                    '__class' => 'CBSelectorTokenField',
                    'name' => 'inner',
                ),
            ));
    }

    public function test_path_parser_3()
    {
        $this->_test_path_parser('[].inner[]', Array(
                Array(
                    '__class' => 'CBSelectorTokenList',
                ),
                Array(
                    '__class' => 'CBSelectorTokenField',
                    'name' => 'inner',
                ),
                Array(
                    '__class' => 'CBSelectorTokenList',
                ),
            ));
    }

    public function test_path_parser_4()
    {
        $this->_test_path_parser('[].inner[].int', Array(
                Array(
                    '__class' => 'CBSelectorTokenList',
                ),
                Array(
                    '__class' => 'CBSelectorTokenField',
                    'name' => 'inner',
                ),
                Array(
                    '__class' => 'CBSelectorTokenList',
                ),
                Array(
                    '__class' => 'CBSelectorTokenField',
                    'name' => 'int',
                ),
            ));
    }

    private function _test_path_parser_error($path)
    {
        $selectorPathParser = $this->factoryDontShowErrors->selectorPathParser();
        $res = $selectorPathParser->parse($path);
        $this->assertNull($res);
    }

    public function test_path_parser_errors()
    {
        $this->_test_path_parser_error('');
        $this->_test_path_parser_error('foo');
        $this->_test_path_parser_error('..');
        $this->_test_path_parser_error('.[]');
        $this->_test_path_parser_error('..');
    }


    public function test_selector_parsing()
    {
        $selector = new CBSelector($this->cbs);

        $reflectionClass = new ReflectionClass($selector);
        $parseSelector = $reflectionClass->getMethod('parseSelector');
        $parseSelector->setAccessible(TRUE);

        $sel = ".foo";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'foo',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = ".foo.bar";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'foo',
            ),
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'bar',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = ".foo[]";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'foo',
            ),
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = ".foo[].bar";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'foo',
            ),
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'bar',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = ".foo[][]";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'foo',
            ),
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = ".foo[][].bar";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'foo',
            ),
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'bar',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = "[].foo";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'foo',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = "[].foo.bar";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'foo',
            ),
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'bar',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = "[].foo[]";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'foo',
            ),
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = "[].foo[].bar";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'foo',
            ),
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'bar',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = "[]";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = "[][]";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
        );
        $this->assertArraysIdentical($expected, $res);

        $sel = "[][].foo";
        $res = $parseSelector->invoke($selector, $sel);
        $expected = Array(
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenList',
            ),
            Array(
                '__class' => 'CBSelectorTokenField',
                'name' => 'foo',
            ),
        );
        $this->assertArraysIdentical($expected, $res);
    }

    public function test_selector_parse_error()
    {
        $selector = new CBSelector($this->cbsDontShowErrors);

        $reflectionClass = new ReflectionClass($selector);
        $parseSelector = $reflectionClass->getMethod('parseSelector');
        $parseSelector->setAccessible(TRUE);

        $sel = "";
        $res = $parseSelector->invoke($selector, $sel);
        $this->assertNull($res);

        $sel = "foo";
        $res = $parseSelector->invoke($selector, $sel);
        $this->assertNull($res);

        $sel = ".";
        $res = $parseSelector->invoke($selector, $sel);
        $this->assertNull($res);

        $sel = ".[]";
        $res = $parseSelector->invoke($selector, $sel);
        $this->assertNull($res);

        $sel = "..";
        $res = $parseSelector->invoke($selector, $sel);
        $this->assertNull($res);
    }

    public function test_selector_compile_error__field_does_not_exist()
    {
        $file = $this->factory->file();

        $sourceType = $this->factory->typeStruct(Array(
                'foo' => $this->factory->typeInt(),
            ));

        $sourceData = Array(
            'foo' => 1,
        );
        $sourceVar = $this->factory->containerValueFromPlainValues($sourceData, $sourceType)
            ->assignToNewVar();
        $file->addVar($sourceVar);
        $sourceVar->name = 'source';

        $selectorString = '.bar';
        $selector = $this->factoryDontShowErrors->selector($file, $sourceVar, $selectorString);
        $this->assertNull($selector);
    }

    public function test_selector_compile_error__selecting_field_not_from_structure()
    {
        $file = $this->factory->file();

        $sourceType = $this->factory->typeInt();
        $sourceData = 1;
        $sourceVar = $this->factory->atomicValue($sourceData, $sourceType)
            ->assignToNewVar();
        $file->addVar($sourceVar);
        $sourceVar->name = 'source';

        $selectorString = '.bar';
        $selector = $this->factoryDontShowErrors->selector($file, $sourceVar, $selectorString);
        $this->assertNull($selector);
    }

    public function test_selector_compile_error__iterating_not_over_list()
    {
        $file = $this->factory->file();

        $sourceType = $this->factory->typeStruct(Array(
                'foo' => $this->factory->typeInt(),
            ));

        $sourceData = Array(
            'foo' => 1,
        );
        $sourceVar = $this->factory->containerValueFromPlainValues($sourceData, $sourceType)
            ->assignToNewVar();
        $file->addVar($sourceVar);
        $sourceVar->name = 'source';

        $selectorString = '.foo[]';
        $selector = $this->factoryDontShowErrors->selector($file, $sourceVar, $selectorString);
        $this->assertNull($selector);
    }

    private function _test_selector_result_type_1_sourceVar()
    {
        $sourceType = $this->factory->typeList(
            $this->factory->typeStruct(Array(
                    'foo' => $this->factory->typeStruct(Array(
                                'bar' => $this->factory->typeList(
                                        $this->factory->typeInt()
                                    ),
                            )),
                ))
        );
        $sourceData = Array(
            Array(
                'foo' => Array(
                    'bar' => Array(1, 2),
                ),
            ),
        );
        return $this->factory->containerValueFromPlainValues($sourceData, $sourceType)
            ->assignToNewVar();
    }

    private function _test_selector_result_type_1($selectorString, $expectedType)
    {
        $file = $this->factory->file();
        $sourceVar = $this->_test_selector_result_type_1_sourceVar();
        $sourceVar->setParentScope($file);

        $selector = $this->factory->selector($file, $sourceVar, $selectorString);
        $this->assertTrue($selector instanceof CBSelector);
        if ($selector instanceof CBSelector) {
            $resultVar = $selector->resVar();
            $res = $resultVar->getType();
            $this->assertArraysIdentical($expectedType, $res);
        }
    }

    /**
     * @TODO: this selector type should also probably give a warning, because it does no changes to the source value,
     * i.e. it does not actually *select* anything
     */
    public function test_selector_result_type_1_1_shouldBeNoChanges()
    {
        $selector = '[]';
        $expected = $this->factory->typeList(
            $this->factory->typeStruct(Array(
                    'foo' => $this->factory->typeStruct(Array(
                                'bar' => $this->factory->typeList(
                                        $this->factory->typeInt()
                                    ),
                            )),
                ))
        );
        $expected->setName('Tmp2');
        $this->_test_selector_result_type_1($selector, $expected);
    }

    public function test_selector_result_type_1_2_structField()
    {
        $selector = '[].foo';
        $expected = $this->factory->typeList(
            $this->factory->typeStruct(Array(
                    'bar' => $this->factory->typeList(
                            $this->factory->typeInt()
                        ),
                ))
        );
        $expected->setName('Tmp2');
        $this->_test_selector_result_type_1($selector, $expected);
    }

    public function test_selector_result_type_1_2_innerStructField()
    {
        $selector = '[].foo.bar';
        $expected = $this->factory->typeList(
            $this->factory->typeList(
                $this->factory->typeInt()
            )
        );
        $expected->setName('Tmp2');
        $this->_test_selector_result_type_1($selector, $expected);
    }

    public function test_selector_result_type_1_3_innerStructFieldMerged()
    {
        $selector = '[].foo.bar[]';
        $expected = $this->factory->typeList(
            $this->factory->typeInt()
        );
        $expected->setName('Tmp2');
        $this->_test_selector_result_type_1($selector, $expected);
    }


    private function _test_selector_from_struct_data()
    {
        return Array(
            'foo' => Array(
                Array(
                    'bar' => Array(1, 2),
                ),
                Array(
                    'bar' => Array(),
                ),
                Array(
                    'bar' => Array(3),
                ),
            ),
        );
    }

    private function _test_selector_from_struct_code($selectorString, $expectedCode)
    {
        $file = $this->factory->file();

        $sourceType = $this->factory->typeStruct(Array(
                'foo' => $this->factory->typeList(
                        $this->factory->typeStruct(Array(
                                'bar' => $this->factory->typeList(
                                        $this->factory->typeInt()
                                    ),
                            ))
                        ),
            ));

        $sourceData = $this->_test_selector_from_struct_data();
        $sourceVar = $this->factory->containerValueFromPlainValues($sourceData, $sourceType)
            ->assignToNewVar();
        $file->addVar($sourceVar);
        $sourceVar->name = 'source';

        $selector = $this->factory->selector($file, $sourceVar, $selectorString);

        $resultVar = $selector->resVar();
        $resultVar->name = 'result';
        $file->addVar($resultVar);

        // Check code generation
        $res = $file->build($this->phpBackend);
        $expected = '<?php

$source = Array(
    \'foo\' => Array(
        Array(
            \'bar\' => Array(
                1,
                2,
            ),
        ),
        Array(
            \'bar\' => Array(),
        ),
        Array(
            \'bar\' => Array(
                3,
            ),
        ),
    ),
);
'.$expectedCode.'
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function test_selector_from_struct_1_code()
    {
        $selector = '.foo';
        $code = '$result = $source[\'foo\'];';
        $this->_test_selector_from_struct_code($selector, $code);
    }

    public function test_selector_from_struct_2_code()
    {
        $selector = '.foo[]';
        $code = '$result = $source[\'foo\'];';
        $this->_test_selector_from_struct_code($selector, $code);
    }

    public function test_selector_from_struct_3_code()
    {
        $selector = '.foo[].bar';
        $code = '// $tmp1;
$result = Array();

foreach ($source[\'foo\'] as $tmp1)
    $result[] = $tmp1[\'bar\'];';
        $this->_test_selector_from_struct_code($selector, $code);
    }

    public function test_selector_from_struct_4_code()
    {
        $selector = '.foo[].bar[]';
        $code = '// $tmp1;
$result = Array();

foreach ($source[\'foo\'] as $tmp1)
    $result = array_merge($result, $tmp1[\'bar\']);';

        $this->_test_selector_from_struct_code($selector, $code);
    }


    private function _test_selector_from_list_code($selectorString, $expectedCode)
    {
        $file = $this->factory->file();

        $sourceType = $this->factory->typeList(
            $this->factory->typeStruct(Array(
                    'foo' => $this->factory->typeStruct(Array(
                                'bar' => $this->factory->typeList(
                                        $this->factory->typeInt()
                                    ),
                            )),
                ))
        );

        $sourceData = Array(
            Array(
                'foo' => Array(
                    'bar' => Array(1, 2),
                ),
            ),
            Array(
                'foo' => Array(
                    'bar' => Array(),
                ),
            ),
            Array(
                'foo' => Array(
                    'bar' => Array(3),
                ),
            ),
        );
        $sourceVar = $this->factory->containerValueFromPlainValues($sourceData, $sourceType)
            ->assignToNewVar();
        $file->addVar($sourceVar);
        $sourceVar->name = 'source';

        $selector = $this->factory->selector($file, $sourceVar, $selectorString);

        $resultVar = $selector->resVar();
        $resultVar->name = 'result';
        $file->addVar($resultVar);

        // Check code generation
        $res = $file->build($this->phpBackend);
        $expected = '<?php

$source = Array(
    Array(
        \'foo\' => Array(
            \'bar\' => Array(
                1,
                2,
            ),
        ),
    ),
    Array(
        \'foo\' => Array(
            \'bar\' => Array(),
        ),
    ),
    Array(
        \'foo\' => Array(
            \'bar\' => Array(
                3,
            ),
        ),
    ),
);
'.$expectedCode.'
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    private function _test_selector_from_nested_list_code($selectorString, $expectedCode, $useResultVar = TRUE)
    {
        $file = $this->factory->file();

        $sourceType = $this->factory->typeList(
            $this->factory->typeStruct(Array(
                    'foo' => $this->factory->typeStruct(Array(
                                'bar' => $this->factory->typeList(
                                        $this->factory->typeStruct(Array(
                                                'baz' => $this->factory->typeList(
                                                        $this->factory->typeInt()
                                                    ),
                                            ))
                                    ),
                            )),
                ))
        );

        $sourceData = Array(
            Array(
                'foo' => Array(
                    'bar' => Array(
                        Array(
                            'baz' => Array(1, 2),
                        ),
                        Array(
                            'baz' => Array(3),
                        ),
                    ),
                ),
            ),
            Array(
                'foo' => Array(
                    'bar' => Array(),
                ),
            ),
            Array(
                'foo' => Array(
                    'bar' => Array(
                        Array(
                            'baz' => Array(5, 6),
                        ),
                    ),
                ),
            ),
        );
        $sourceVar = $this->factory->containerValueFromPlainValues($sourceData, $sourceType)
            ->assignToNewVar();
        $file->addVar($sourceVar);
        $sourceVar->name = 'source';

        $selector = $this->factory->selector($file, $sourceVar, $selectorString);

        if ($useResultVar) {
            $resultVar = $selector->resVar();
            $resultVar->name = 'result';
            $file->addVar($resultVar);
        }

        // Check code generation
        $res = $file->build($this->phpBackend);
        $expected = '<?php

$source = Array(
    Array(
        \'foo\' => Array(
            \'bar\' => Array(
                Array(
                    \'baz\' => Array(
                        1,
                        2,
                    ),
                ),
                Array(
                    \'baz\' => Array(
                        3,
                    ),
                ),
            ),
        ),
    ),
    Array(
        \'foo\' => Array(
            \'bar\' => Array(),
        ),
    ),
    Array(
        \'foo\' => Array(
            \'bar\' => Array(
                Array(
                    \'baz\' => Array(
                        5,
                        6,
                    ),
                ),
            ),
        ),
    ),
);
';
        if ($expectedCode) {
            $expected .= $expectedCode.$this->cbs->eol;
        }

        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function test_selector_from_list_1_code()
    {
        $selector = '[]';
        $code = '$result = $source;';
        $this->_test_selector_from_list_code($selector, $code);
    }

    public function test_selector_from_list_2_code()
    {
        $selector = '[].foo';
        $code = '// $tmp1;
$result = Array();

foreach ($source as $tmp1)
    $result[] = $tmp1[\'foo\'];';
        $this->_test_selector_from_list_code($selector, $code);
    }

    public function test_selector_from_list_3_code()
    {
        $selector = '[].foo.bar';
        $code = '// $tmp1;
$result = Array();

foreach ($source as $tmp1)
    $result[] = $tmp1[\'foo\'][\'bar\'];';

        $this->_test_selector_from_list_code($selector, $code);
    }

    public function test_selector_from_list_4_code()
    {
        $selector = '[].foo.bar[]';
        $code = '// $tmp1;
$result = Array();

foreach ($source as $tmp1)
    $result = array_merge($result, $tmp1[\'foo\'][\'bar\']);';

        $this->_test_selector_from_list_code($selector, $code);
    }

    public function test_selector_from_nested_list_dont_merge_result__code()
    {
        $selector = '[].foo.bar[].baz';
        $code = '// $tmp1;
// $tmp2;
$result = Array();

foreach ($source as $tmp1)
    foreach ($tmp1[\'foo\'][\'bar\'] as $tmp2)
        $result[] = $tmp2[\'baz\'];';


        $this->_test_selector_from_nested_list_code($selector, $code);
    }

    public function test_selector_from_nested_list_merge_result__code()
    {
        $selector = '[].foo.bar[].baz[]';
        $code = '';

        $this->_test_selector_from_nested_list_code($selector, $code, FALSE);
    }

    public function test_selector_from_nested_list__never_used__code()
    {
        $selector = '[].foo.bar[].baz[]';
        $code = '// $tmp1;
// $tmp2;
$result = Array();

foreach ($source as $tmp1)
    foreach ($tmp1[\'foo\'][\'bar\'] as $tmp2)
        $result = array_merge($result, $tmp2[\'baz\']);';

        $this->_test_selector_from_nested_list_code($selector, $code);
    }


    public function test_mutvar__calculate_sum_of_values()
    {
        $file = new CBFile($this->cbs);

        $sourceList = Array(
            Array(
                'int' => 1,
            ),
            Array(
                'int' => 2,
            ),
            Array(
                'int' => 3,
            ),
        );

        $listType = $this->factory->typeList(
            $this->factory->typeStruct(Array(
                'int' => $this->factory->typeInt()
            ))
        );
        $listVar = $this->factory->containerValueFromPlainValues($sourceList, $listType)->assignToNewVar();
        $file->addVar($listVar);
        $listVar->name = 'list';

        $accumulatorVar = $this->factory->mutVar(
            $this->factory->atomicValue(0, $this->factory->typeInt()));
        $file->addVar($accumulatorVar);
        $accumulatorVar->setName('accumulator');

        $listIterator = $this->factory->listIterator($listVar);
        $file->addBlock($listIterator);
        $iteratorVar = $listIterator->getIteratorVar();

        $assignment = $this->factory->mutVarAssignment($accumulatorVar);
        $assignment->setAddValue($iteratorVar->refVal()->toStruct()->getValueForKey('int'));
        $listIterator->addMutVarAssignment($assignment);

        // Check code generation
        $res = $file->build($this->phpBackend);
        $expected = '<?php

$list = Array(
    Array(
        \'int\' => 1,
    ),
    Array(
        \'int\' => 2,
    ),
    Array(
        \'int\' => 3,
    ),
);
$accumulator = 0;
// $tmp1;

foreach ($list as $tmp1)
    $accumulator += $tmp1[\'int\'];
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }


    private function _test_transforming_struct_in_list_file()
    {
        $file = new CBFile($this->cbs);

        $sourceType = $this->factory->typeList(
            $this->factory->typeStruct(Array(
                    'inner' => $this->factory->typeList(
                            $this->factory->typeStruct(Array(
                                    'int' => $this->factory->typeInt(),
                                ))),
                ))
        );
        $source = Array(
            Array(
                'inner' => Array(
                    Array(
                        'int' => 1,
                    ),
                    Array(
                        'int' => 2,
                    ),
                ),
            ),
            Array(
                'inner' => Array(),
            ),
            Array(
                'inner' => Array(
                    Array(
                        'int' => 3,
                    ),
                ),
            ),
        );

        // sourceVar = [...]
        $sourceVar = $this->factory->containerValueFromPlainValues($source, $sourceType)->assignToNewVar();
        $file->addVar($sourceVar);
        $sourceVar->name = 'sourceVar';

        // @TODO: there should be a mutable_variable_type_modification class, that would be used
        // when creating a mutable variable to indicate changes to its type.
        // The purpose is to make sure that any type modifications are done during the construction
        // of a mutable variable and that once it is constructed - its type does not change,
        // to prevent possible ugly problems.

        // resultMutVar = sourceVar
        $resultMutVar = $this->factory->mutVar($sourceVar->refVal());
        $file->addVar($resultMutVar);
        $resultMutVar->name = 'resultMutVar';
        $resultMutVar->getType()->itemType->fieldTypes['intList'] = $this->factory->typeList($this->factory->typeInt());

        // foreach (resultMutVar as listIteratorIndexVar => listIteratorVar)
        $listIterator = $this->factory->listIterator($resultMutVar);
        $file->addBlock($listIterator);
        $listIteratorVar = $listIterator->getIteratorVar();
        $listIteratorVar->name = 'listIteratorVar';
        $listIteratorIndexVar = $listIterator->getIndexVar();
        $listIteratorIndexVar->name = 'listIteratorIndexVar';

        //      intListMutVar = []
        $intListType = $this->factory->typeList($this->factory->typeInt());
        $intListInitialValue = $this->factory->newListValue($intListType);
        $intListInitialValue->val = Array();
        $intListMutVar = $this->factory->mutVar($intListInitialValue);
        $listIterator->addVar($intListMutVar);
        $intListMutVar->name = 'intListMutVar';

        //      innerArrayVar = listIteratorVar['inner']
        $innerArrayValue = $listIteratorVar->refVal()->toStruct()->getValueForKey('inner');
        $innerArrayVar = $innerArrayValue->assignToNewVar();
        $listIterator->addVar($innerArrayVar);
        $innerArrayVar->name = 'innerArrayVar';

        //      foreach (innerArrayVar as innerListIteratorVar)
        $innerListIterator = $this->factory->listIterator($innerArrayVar);
        $listIterator->addBlock($innerListIterator);
        $innerListIteratorVar = $innerListIterator->getIteratorVar();
        $innerListIteratorVar->name = 'innerListIteratorVar';

        //          intListMutVar[] = innerListIteratorVar['int']
        $innerListIterator_intValue = $innerListIteratorVar->refVal()->toStruct()->getValueForKey('int');
        $intListMutVarAssignment = $this->factory
            ->mutVarAssignment($intListMutVar)
            ->setListAppendValue($innerListIterator_intValue);
        $innerListIterator->addMutVarAssignment($intListMutVarAssignment);

        //      result[listIteratorIndexVar]['intList'] = intListMutVar
        $result_listIterator_intList = $this->factory->varPath(
            $resultMutVar
            , Array($listIteratorIndexVar->refVal(), 'intList'));
        $result_listIterator_intList_assignment = $this->factory
            ->mutVarAssignment($result_listIterator_intList)
            ->setAssignValue($intListMutVar->refVal());
        $listIterator->addMutVarAssignment($result_listIterator_intList_assignment);

        return $file;
    }

    public function test_transforming_struct_in_list_code()
    {
        $file = $this->_test_transforming_struct_in_list_file();

        // Check code generation
        $res = $file->build($this->phpBackend);
        $expected = '<?php

$sourceVar = Array(
    Array(
        \'inner\' => Array(
            Array(
                \'int\' => 1,
            ),
            Array(
                \'int\' => 2,
            ),
        ),
    ),
    Array(
        \'inner\' => Array(),
    ),
    Array(
        \'inner\' => Array(
            Array(
                \'int\' => 3,
            ),
        ),
    ),
);
$resultMutVar = $sourceVar;
// $listIteratorVar;
// $listIteratorIndexVar;
// $intListMutVar;
// $innerArrayVar;
// $innerListIteratorVar;

foreach ($resultMutVar as $listIteratorIndexVar => $listIteratorVar)
{
    $intListMutVar = Array();
    $innerArrayVar = $listIteratorVar[\'inner\'];

    foreach ($innerArrayVar as $innerListIteratorVar)
        $intListMutVar[] = $innerListIteratorVar[\'int\'];

    $resultMutVar[$listIteratorIndexVar][\'intList\'] = $intListMutVar;
}
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function test_transforming_struct_in_list_data()
    {
        $file = $this->_test_transforming_struct_in_list_file();
        $code = $file->build($this->phpBackend);
        $res = str_replace('<?php', '', $code);
        $resultMutVar = NULL;
        eval($res);
        $expected = Array(
            Array(
                'inner' => Array(
                    Array(
                        'int' => 1,
                    ),
                    Array(
                        'int' => 2,
                    ),
                ),
                'intList' => Array(1, 2),
            ),
            Array(
                'inner' => Array(),
                'intList' => Array(),
            ),
            Array(
                'inner' => Array(
                    Array(
                        'int' => 3,
                    ),
                ),
                'intList' => Array(3),
            ),
        );
        $this->assertArraysIdentical($expected, $resultMutVar);
    }


    public function testIf()
    {
        $file = new CBFile($this->cbs);
        $function = new CBFunction($this->cbs, 'fn1');
        $file->addFn($function);

        $var1 = CBValue::factoryFromValue($this->cbs, 'test')->assignToNewVar();
        $function->addVar($var1);

        $if = new CBIf($this->cbs);
        $function->addIf($if);

        $predicate = new CBPredicate($this->cbs);
        $if->predicates[] = $predicate;
        $predicate->left = $var1;
        $predicate->operator = '==';
        $predicate->right = CBValue::factoryFromValue($this->cbs, 'test');

        $var2 = CBValue::factoryFromValue($this->cbs, 'test 2')->assignToNewVar();
        $if->block->addVar($var2);
        $var2->name = 'var2';

        $res = $file->build($this->phpBackend);
        $expected = '<?php

function fn1()
{
    $tmp1 = \'test\';
    // $var2;

    if ($tmp1 == \'test\')
        $var2 = \'test 2\';
}
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);

        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function testClassObject()
    {
        $file = new CBFile($this->cbs);
        $class = new CBClass($this->cbs, 'TestClass');
        $file->addClass($class);
        $obj = $class->newObject();
        $objVar = $obj->assignToNewVar();
        $objVar->name = 'obj';
        $file->addVar($objVar);

        $file->return = $objVar;

        $res = $file->build($this->phpBackend);
        $expected = '<?php

class TestClass
{
}

$obj = new TestClass();

return $obj;
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function test_object_property_assignment()
    {
        $file = new CBFile($this->cbs);

        $classRef = new CBClassRef($this->cbs, 'TestClass');
        $objVar = $classRef->newObj()->assignToNewVar();
        $file->addVar($objVar);
        $objVar->name = 'obj';
        $paramVar = $objVar->value->toObject()->getDynamicProp('param');
        $paramVar->value = CBValue::factoryFromValue($this->cbs, 1);

        $res = $file->build($this->phpBackend);
        $expected = '<?php

$obj = new TestClass();
$obj->param = 1;
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function test_assigning_this_to_object_dynamic_property()
    {
        $file = new CBFile($this->cbs);

        $testClass = new CBClass($this->cbs, 'TestClass');
        $file->addClass($testClass);

        $method1 = new CBFunction($this->cbs, 'method1');
        $testClass->addFn($method1);

        $method1This = $method1->getThis();

        $classRef = new CBClassRef($this->cbs, 'TestClass');
        $objVar = $classRef->newObj()->assignToNewVar();
        $method1->addVar($objVar);
        $objVar->name = 'obj';
        $paramVar = $objVar->value->toObject()->getDynamicProp('param');
        $paramVar->value = $method1This;

        $res = $file->build($this->phpBackend);
        $expected = '<?php

class TestClass
{
    function method1()
    {
        $obj = new TestClass();
        $obj->param = $this;
    }
}
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function testObjectMethodCall()
    {
        $file = new CBFile($this->cbs);
        $class = new CBClass($this->cbs, 'TestClass');
        $file->addClass($class);
        $function = new CBFunction($this->cbs, 'fn1');
        $class->addFn($function);

        $obj = $class->newObject();
        $objVar = $obj->assignToNewVar();
        $objVar->name = 'obj';
        $file->addVar($objVar);
        $file->return = $objVar->refVal()->callFn('fn1')->res();

        $res = $file->build($this->phpBackend);
        $expected = '<?php

class TestClass
{
    function fn1()
    {
    }
}

$obj = new TestClass();

return $obj->fn1();
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function testClassMethodCallInAnotherMethod()
    {
        $file = new CBFile($this->cbs);
        $class = new CBClass($this->cbs, 'TestClass');
        $file->addClass($class);

        $fn1 = new CBFunction($this->cbs, 'fn1');
        $class->addFn($fn1);

        $fn2 = new CBFunction($this->cbs, 'fn2');
        $class->addFn($fn2);
        $fn2->return = $class->callFn('fn1')->res();

        $obj = $class->newObject();
        $objVar = $obj->assignToNewVar();
        $objVar->name = 'obj';
        $file->addVar($objVar);
        $file->return = $objVar->refVal()->callFn('fn2')->res();

        $res = $file->build($this->phpBackend);
        $expected = '<?php

class TestClass
{
    function fn1()
    {
    }

    function fn2()
    {
        return TestClass::fn1();
    }
}

$obj = new TestClass();

return $obj->fn2();
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function testObjectMethodCallInAnotherMethod()
    {
        $file = new CBFile($this->cbs);

        $class = new CBClass($this->cbs, 'TestClass');
        $file->addClass($class);

        $fn1 = new CBFunction($this->cbs, 'fn1');
        $class->addFn($fn1);

        $fn2 = new CBFunction($this->cbs, 'fn2');
        $class->addFn($fn2);

        $thisObj = $fn2->getThis();
        $fn2->return = $thisObj->callFn('fn1')->res();

        $obj = $class->newObject();
        $objVar = $obj->assignToNewVar();
        $objVar->name = 'obj';
        $file->addVar($objVar);
        $file->return = $objVar->refVal()->callFn('fn2')->res();

        $res = $file->build($this->phpBackend);
        $expected = '<?php

class TestClass
{
    function fn1()
    {
    }

    function fn2()
    {
        return $this->fn1();
    }
}

$obj = new TestClass();

return $obj->fn2();
';
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function testValueTakenFromSuperGlobalShouldBeMarkedForeign()
    {
        $file = new CBFile($this->cbs);

        $var = new CBVariable($this->cbs);
        $file->addVar($var);
        $var->name = '_SUPER_GLOBAL';
        $var->superGlobal = TRUE;

        $val = $var->refVal();
        $this->assertTrue($val->source->isForeign());
    }

    public function testCBStruct_fromSuperGlobal_getValueForKey()
    {
        $file = new CBFile($this->cbs);

        $globalVal = new CBStruct($this->cbs);
        $globalVal->type->fieldTypes = Array(
            'key' => $this->factory->typeInt(),
        );

        $structVar = $globalVal->assignToNewVar();
        $file->addVar($structVar);
        $structVar->name = '_GLOBAL_STRUCT';
        $structVar->superGlobal = TRUE;

        $file->return = $structVar->refVal()->toStruct()->getValueForKey('key');

        $res = $file->build($this->phpBackend);
        $expected = <<<'EOD'
<?php

return $_GLOBAL_STRUCT['key'];

EOD;
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function test_CBString_escape()
    {
        $file = new CBFile($this->cbs);

        $string = CBValue::factoryFromValue($this->cbs, "String with 'quoted text'");
        $var = $string->assignToNewVar();
        $file->addVar($var);

        $res = $file->build($this->phpBackend);
        $expected = <<<'EOD'
<?php

$tmp1 = 'String with \'quoted text\'';

EOD;
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function test_callFn_on_object_stored_in_variable()
    {
        $file = new CBFile($this->cbs);

        $TestClassRef = new CBClassRef($this->cbs, 'TestClass');
        $testObjVar = $TestClassRef->newObj()->assignToNewVar();
        $file->addVar($testObjVar);
        $testObjVar->name = 'testObject';

        $requestDataVar = $testObjVar->refVal()->toObject()->callFn('someMethod')->res()->assignToNewVar();
        $file->addVar($requestDataVar);
        $requestDataVar->name = 'someMethodResult';

        $res = $file->build($this->phpBackend);
        $expected = <<<'EOD'
<?php

$testObject = new TestClass();
$someMethodResult = $testObject->someMethod();

EOD;
        $expected = str_replace("\r\n", $this->cbs->eol, $expected);
        $this->assertEqual($expected, $res);
        string_diff($expected, $res);
    }

    public function test_c_get_struct_field_from_function_result()
    {
        $block = new CBTestNamingBlock($this->cbs);
        $block->bracesMode = CBBlock::BRACES_MODE_NEVER;

        $fnCall = new CBFunctionCall($this->cbs, NULL, 'some_foreign_function');
        $fnCallResValue = $fnCall->res()->value();

        $type = $this->cbs->factory->typeStruct();
        $type->setExternalTypeName('ForeignFunctionResultStruct');
        $type->fieldTypes['innerIntValueKey'] = $this->cbs->factory->typeInt();
        $fnCallResValue->type = $type;

        $fnCallResVar = $fnCallResValue->assignToNewVar();
        $block->addVar($fnCallResVar);
        $fnCallResVar->name = 'foreign_function_result';

        $innerIntValue = $fnCallResVar->refVal()->toStruct()->getValueForKey('innerIntValueKey');
        $innerIntValueVar = $innerIntValue->assignToNewVar();
        $block->addVar($innerIntValueVar);
        $innerIntValueVar->name = 'innerIntValue';

        $expected = Array(
            'php' =>
'$foreign_function_result = some_foreign_function();
$innerIntValue = $foreign_function_result[\'innerIntValueKey\'];',
            'c' =>
'const ForeignFunctionResultStruct foreign_function_result = some_foreign_function();
const int innerIntValue = foreign_function_result.innerIntValueKey;',
        );
        $this->_test($block, $expected);
    }

    private function _test(CBBlock $block, array $codeForBackends)
    {
        if (isset($codeForBackends['php'])) {
            $res = $block->build($this->phpBackend);
            $expected = str_replace("\r\n", $this->cbs->eol, $codeForBackends['php']);
            $this->assertEqual($expected, $res);
            string_diff($expected, $res);
        }

        if (isset($codeForBackends['c'])) {
            $res = $block->build($this->cBackend);
            $expected = str_replace("\r\n", $this->cbs->eol, $codeForBackends['c']);
            $this->assertEqual($expected, $res);
            string_diff($expected, $res);
        }
    }
}
