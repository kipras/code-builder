<?php

require_once dirname(__FILE__) . '/interfaces/ICBHasFunctions.php';
require_once dirname(__FILE__) . '/interfaces/ICBHasParentScope.php';
require_once dirname(__FILE__) . '/interfaces/ICBNamedEntity.php';

require_once dirname(__FILE__) . '/_base/CBEntity.php';
require_once dirname(__FILE__) . '/_base/CBBuildableEntity.php';

require_once dirname(__FILE__) . '/CBFactory.php';
require_once dirname(__FILE__) . '/CBFileBuildContext.php';
require_once dirname(__FILE__) . '/CBFinal.php';
require_once dirname(__FILE__) . '/CBSettings.php';
require_once dirname(__FILE__) . '/CBUtil.php';
require_once dirname(__FILE__) . '/CBValueSource.php';

require_once dirname(__FILE__) . '/Backend/_base/CBBackend.php';
require_once dirname(__FILE__) . '/Backend/CBCBackend.php';
require_once dirname(__FILE__) . '/Backend/CBPhpBackend.php';

require_once dirname(__FILE__) . '/Scope/_base/CBScope.php';
require_once dirname(__FILE__) . '/Scope/CBClass.php';
require_once dirname(__FILE__) . '/Scope/Block/_base/CBBlock.php';
require_once dirname(__FILE__) . '/Scope/Block/CBEach.php';
require_once dirname(__FILE__) . '/Scope/Block/CBFile.php';
require_once dirname(__FILE__) . '/Scope/Block/CBFunction.php';
require_once dirname(__FILE__) . '/Scope/Block/CBListIterator.php';

require_once dirname(__FILE__) . '/Entity/Entity/CBClassRef.php';
require_once dirname(__FILE__) . '/Entity/Entity/CBFunctionCallResult.php';

require_once dirname(__FILE__) . '/Entity/Entity/CBType/_base/CBType.php';
require_once dirname(__FILE__) . '/Entity/Entity/CBType/CBTypeAtomic.php';
require_once dirname(__FILE__) . '/Entity/Entity/CBType/CBTypeList.php';
require_once dirname(__FILE__) . '/Entity/Entity/CBType/CBTypeObject.php';
require_once dirname(__FILE__) . '/Entity/Entity/CBType/CBTypeStruct.php';
require_once dirname(__FILE__) . '/Entity/Entity/CBType/CBTypeUnknown.php';

require_once dirname(__FILE__) . '/Entity/BuildableEntity/CBFunctionCall.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/CBFunctionCall.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/CBFunctionParameter.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/CBIf.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/CBMutVarAssignment.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/CBPredicate.php';

require_once dirname(__FILE__) . '/Entity/BuildableEntity/Value/factory/CBContainerValueFactory.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/Value/factory/CBContainerValueListFactory.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/Value/factory/CBContainerValueStructFactory.php';

require_once dirname(__FILE__) . '/Entity/BuildableEntity/Value/_base/CBValue.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/Value/CBList.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/Value/CBObject.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/Value/CBStruct.php';

require_once dirname(__FILE__) . '/Entity/BuildableEntity/Variable/_base/CBBaseVariable.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/Variable/CBVariable.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/Variable/CBMutVar.php';

require_once dirname(__FILE__) . '/Entity/BuildableEntity/VarPath/CBVarPath.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/VarPath/CBVarPathListIndex.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/VarPath/CBVarPathListItem.php';
require_once dirname(__FILE__) . '/Entity/BuildableEntity/VarPath/CBVarPathStructField.php';

require_once dirname(__FILE__) . '/hl/selector/CBSelector.php';
require_once dirname(__FILE__) . '/hl/selector/CBSelectorPathParser.php';
require_once dirname(__FILE__) . '/hl/selector/tokens/CBSelectorToken.php';
require_once dirname(__FILE__) . '/hl/selector/tokens/CBSelectorTokenField.php';
require_once dirname(__FILE__) . '/hl/selector/tokens/CBSelectorTokenList.php';
