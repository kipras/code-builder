<?php
class CBCBackend extends CBBackend
{
    public function endOfStatement()
    {
        return ';';
    }

    public function buildFileHeader(CBFile $file)
    {
        return '';
    }

    public function buildMainFunction($code)
    {
        $code .= $this->cbs->eol.$this->cbs->eol;
        $code .= 'return 0;';

        $lines = Array(
            'int main()',
            '{',
            $this->cbs->indent(1, $code),
            '}',
        );
        return join($this->cbs->eol, $lines);
    }

    public function buildTypeDefinition(CBType $type)
    {
        $r = '';
        if ($type->hasToBeDeclared()) {
            if ($type instanceof CBTypeStruct) {
                $typeName = $type->getName();
                $r = "typedef struct _{$typeName} {".$this->cbs->eol;
                foreach ($type->fieldTypes as $key => $t) {
                    $r .= $this->cbs->indent(1).$this->buildType($t)." {$key};".$this->cbs->eol;
                }
                $r .= "} {$typeName}";
            }
        }

        return $r;
    }

    public function buildVarUninitializedDeclaration(CBVariable $var)
    {
        return $this->buildVarDeclarationWithType($var);
    }

    public function buildVarInitializedDeclaration(CBBaseVariable $var, CBScope $scope)
    {
        return $this->buildAssignment(
            $this->buildVarDeclarationWithType($var)
            , $this->buildVarInitializer($var, $scope));
    }

    public function buildStringVal($string)
    {
        return '"'.str_replace('"', '\"', $string).'"';
    }

    public function buildListInitializer(array $list, CBScope $scope)
    {
        $code = '{' . $this->cbs->eol;
        foreach ($list as $v){
            $valCode = $this->buildVal($v, $scope);
            $valCode .= ',' . $this->cbs->eol;

            $valCode = $this->cbs->indent(1, $valCode);

            $code .= $valCode;
        }
        $code .= '}';

        return $code;
    }

    public function buildListIndexAccessor($index)
    {
        return "[".$index."]";
    }

    public function buildListIterator($pathToList, $pathToItem, $pathToIndex = NULL)
    {
        // @TODO: Implement me!
        $this->error("Implement me", "Implement me");
    }

    public function buildAddToList($pathToList, $pathToItem)
    {
        // @TODO: Implement me!
        $this->error("Implement me", "Implement me");
    }

    public function buildMergeLists($pathToFirstList, $pathToSecondList)
    {
        // @TODO: Implement me!
        $this->error("Implement me", "Implement me");
    }

    public function buildStructInitializer(array $struct, CBScope $scope)
    {
        $code = '{' . $this->cbs->eol;
        foreach ($struct as $k => $v){
            $valCode = '.' . $k;
            $valCode .= " = ";
            $valCode .= $this->buildVal($v, $scope);
            $valCode .= ',' . $this->cbs->eol;

            $valCode = $this->cbs->indent(1, $valCode);

            $code .= $valCode;
        }
        $code .= '}';

        return $code;
    }

    public function buildStructFieldAccessor($fieldName)
    {
        return '.'.$fieldName;
    }

    public function buildThis()
    {
        // @TODO: Implement me!
        $this->error("Implement me", "Implement me");
    }


    /**
     * @param CBBaseVariable $var
     * @return string
     */
    private function buildVarDeclarationWithType(CBBaseVariable $var)
    {
        $typePrefix = NULL;
        $listSuffix = NULL;
        $varName = $this->varName($var);
        $type = $var->getType();

        $typeName = $type->getTypeName();
        if ($typeName !== NULL) {
            $typePrefix = $typeName;
        } else {
            if ($type instanceof CBTypeList) {
                $typePrefix = $this->buildType($type->itemType);
                $listSuffix = '[]';
            } else if ($type instanceof CBTypeStruct) {
                $typePrefix = $this->buildType($type);
            } else if ($type instanceof CBTypeAtomic) {
                $typePrefix = $this->buildType($type);
            }
        }

        if ($typePrefix === NULL) {
            $this->cbs->error(__METHOD__.": unexpected type encountered", CBSettings::ERROR_UNEXPECTED_TYPE);
            return NULL;
        } else {
            $r = 'const '.$typePrefix.' '.$varName;
            if ($listSuffix) {
                $r .= $listSuffix;
            }
            return $r;
        }
    }

    /**
     * @param CBType $type
     * @return null|string
     */
    private function buildType(CBType $type)
    {
        $typeName = $type->getTypeName();
        if ($typeName !== NULL) {
            return $typeName;
        }

        if ($type instanceof CBTypeList) {
            return $this->buildType($type->itemType).'*';
        } else if ($type instanceof CBTypeStruct) {
            // @TODO: implement me!
            $this->cbs->error("Implement me!", CBSettings::ERROR_UNEXPECTED_TYPE);
        } else if ($type instanceof CBTypeAtomic) {
            switch ($type->typeFlag) {
                case CBType::CB_STRING:
                    return 'char*';

                case CBType::CB_INT:
                    return 'int';

                case CBType::CB_FLOAT:
                    return 'double';

                case CBType::CB_BOOL:
                    return 'bool';
            }
        }

        return NULL;
    }
}
