<?php

/**
 * Class CBType
 * This class and it's subclasses represent the type system of CodeBuilder
 * @TODO: not sure if CB_OBJECT is necessary. Perhaps for interfacing with foreign PHP code ?
 */
abstract class CBType extends CBEntity implements ICBHasParentScope, ICBNamedEntity
{
    // Types use a CB_ prefix, because LIST is a reserved word and cannot be used
    // These values must be the same as the corresponding K__LIB_TYPE_ constants in DepotApi for all backends
    //
    // Atomic types
    const CB_STRING     = 1;
    const CB_INT        = 2;
    const CB_FLOAT      = 3;
    const CB_BOOL       = 4;


    /**
     * @var CBScope
     */
    private $_parentScope;

    /**
     * @var string
     */
    private $_name;

    /**
     * @var bool If this is FALSE, then the type name is automatically generated by CBScope->_addToScope().
     * If this is TRUE however, then the type name is manually set by ->setTypeName() and it represents
     * an actual human readable type name, in which case the CodeBuilder should compile a type declaration
     * (if required by the output language) and use this custom type name when declaring variables.
     *
     * It all works this way, because we need CBType to implement ICBNamedEntity so we could use the standard
     * CBScope->_addToScope() for name uniqueness checking, to make sure that two types cannot have the same name.
     * But we also need to know when type names are automatically assigned and when they are custom human-readable names.
     */
    public $hasTypeName = FALSE;

    /**
     * @var string|null If this type is declared externally (and should not be declared in this scope) then this holds
     * the name of that external type
     */
    private $externalName = NULL;


    protected function atomicTypeFlags()
    {
        return [
            CBType::CB_STRING, CBType::CB_INT, CBType::CB_FLOAT, CBType::CB_BOOL,
        ];
    }


    public function __toArray()
    {
        return Array(
            'name' => $this->getTypeName(),
            'external' => $this->isExternal(),
            'toString' => $this->toString(),
        );
    }


    /**
     * @return CBScope
     * @see ICBHasParentScope::getParentScope()
     */
    public function getParentScope()
    {
        return $this->_parentScope;
    }

    /**
     * @param CBScope $scope
     * @see ICBHasParentScope::setParentScope()
     */
    public function setParentScope(CBScope $scope)
    {
        $this->_parentScope = $scope;
    }


    /**
     * @return string|null
     * @TODO: should be renamed to getScopeName() to indicate that this method should only be used for scope
     * collisions checking
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param string $name
     * @TODO: should be renamed to getScopeName() to indicate that this method should only be used for scope
     * collisions checking
     */
    public function setName($name)
    {
        // If we are changing the name of this type - also make sure that the parent scope is updated
        $this->_name = $name;
        if ($this->getParentScope() !== NULL) {
            $this->getParentScope()->addType($this);
        }
    }


    /**
     * Use this method to retrieve type name instead of ->getName().
     * ->getName() should only be used for scope collisions checking.
     * @return string|null
     */
    public function getTypeName()
    {
        if ($this->externalName) {
            return $this->externalName;
        } else if ($this->hasTypeName) {
            return $this->_name;
        }
        return NULL;
    }

    /**
     * Sets custom, human-readable type name. If you need to set a real type name for this type - use this method,
     * not ->setName(), because ->setName() is also used by CBScope to automatically set a unique name for every
     * item added to a scope.
     * Use this method, so we could identify if the name was specifically set or automatically generated.
     * @param string $name
     */
    public function setTypeName($name)
    {
        $this->setName($name);
        $this->hasTypeName = TRUE;
    }

    /**
     * External type name does not belong to a scope
     * @param string $name
     */
    public function setExternalTypeName($name)
    {
        $this->externalName = $name;
    }


    /**
     * @return bool TRUE if this is an external type, FALSE otherwise
     */
    public function isExternal()
    {
        return ($this->externalName !== NULL);
    }


    public function isAtomic()
    {
        return $this instanceof CBTypeAtomic;
    }
    public function isString()
    {
        return ($this instanceof CBTypeAtomic AND $this->typeFlag == CBType::CB_STRING);
    }
    public function isInt()
    {
        return ($this instanceof CBTypeAtomic AND $this->typeFlag == CBType::CB_INT);
    }
    public function isFloat()
    {
        return ($this instanceof CBTypeAtomic AND $this->typeFlag == CBType::CB_FLOAT);
    }
    public function isNumber()
    {
        return ($this->isInt() OR $this->isFloat());
    }
    public function isBool()
    {
        return ($this instanceof CBTypeAtomic AND $this->typeFlag == CBType::CB_BOOL);
    }
    public function isObject()
    {
        return $this instanceof CBTypeObject;
    }
    public function isList()
    {
        return $this instanceof CBTypeList;
    }
    public function isStruct()
    {
        return $this instanceof CBTypeStruct;
    }

    /**
     * @return bool TRUE if this type cannot be expressed (inline in a variable declaration) and therefore needs
     * to be named and declared separately.
     */
    public function hasToBeDeclared()
    {
        // @TODO: implement me! Structural types should not be declared inline (for better readability) and should
        // instead be declared in a separate place
        return $this->hasTypeName;
    }

    /**
     * @return string|null Returns string representation of this type
     */
    public function toString()
    {
        if ($this instanceof CBTypeAtomic) {
            return $this->atomicTypeToString($this->typeFlag);
        } else if ($this instanceof CBTypeList) {
            return '['.$this->itemType->toString().']';
        } else if ($this instanceof CBTypeStruct) {
            $fieldTypes = [];
            foreach ($this->fieldTypes as $field => $type) {
                $fieldTypes[] = $field.': '.$type->toString();
            }
            $fieldTypes = join(', ', $fieldTypes);
            return '{'.$fieldTypes.'}';
        } else {
            $this->_cbs->unexpectedTypeError();
        }
    }

    private function atomicTypeToString($typeFlag)
    {
        $TYPE_STRINGS = Array(
            self::CB_BOOL => 'bool',
            self::CB_FLOAT => 'float',
            self::CB_INT => 'int',
            self::CB_STRING => 'string',
        );

        return $TYPE_STRINGS[$typeFlag];
    }


    /**
     * @param CBType $otherType
     * @return bool TRUE if this type is a proper super-type or is identical to $otherType, FALSE otherwise
     * @TODO: implement me properly!
     * Right now we are just checking if types are identical. But later, if/when we will have type polymorphism
     * (subtyping and/or parametric polymorphism) - we will need to modify this to work correctly.
     * However this method is already used in some places of CodeBuilder type system in preparation for that.
     */
    public function matchesOrIsSuperTypeOf(CBType $otherType)
    {
        return $this->isTypeIdentical($otherType);
    }

    /**
     * @param CBType $otherType
     * @return bool TRUE if this type is identical to $otherType, FALSE otherwise
     */
    public function validateTypeIsIdentical(CBType $otherType)
    {
        $valid = $this->isTypeIdentical($otherType);
        if (FALSE == $valid) {
            $this->_cbs->error(
                'Type '.$this->toString().' is not identical to type '.$otherType->toString()
                , CBSettings::ERROR_TYPE_SYSTEM);
        }
        return $valid;
    }

    /**
     * @param CBType $otherType
     * @return bool TRUE if this type is identical to $otherType, FALSE otherwise
     */
    public function isTypeIdentical(CBType $otherType)
    {
        if ($this->externalName != $otherType->externalName) {
            return FALSE;
        }
        if ($this->hasTypeName != $otherType->hasTypeName) {
            return FALSE;
        }
        if ($this->hasTypeName AND ($this->getName() != $otherType->getName())) {
            return FALSE;
        }

        if ($this instanceof CBTypeAtomic) {
            if ($otherType instanceof CBTypeAtomic) {
                return $this->typeFlag == $otherType->typeFlag;
            } else {
                return FALSE;
            }
        } else if ($this instanceof CBTypeList) {
            if ($otherType instanceof CBTypeList) {
                return $this->itemType->isTypeIdentical($otherType->itemType);
            } else {
                return FALSE;
            }
        } else if ($this instanceof CBTypeStruct) {
            if ($otherType instanceof CBTypeStruct) {
                $diff1 = array_diff(array_keys($this->fieldTypes), array_keys($otherType->fieldTypes));
                $diff2 = array_diff(array_keys($this->fieldTypes), array_keys($otherType->fieldTypes));
                if ($diff1 OR $diff2) {
                    return FALSE;
                }
                foreach ($this->fieldTypes as $field => $fieldType) {
                    if (FALSE == $fieldType->isTypeIdentical($otherType->fieldTypes[$field])) {
                        return FALSE;
                    }
                }
                return TRUE;
            } else {
                return FALSE;
            }
        }

        return TRUE;
    }
}
