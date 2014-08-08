<?php
class CBPhpBackend extends CBBackend
{
    public function endOfStatement()
    {
        return ';';
    }

    public function buildFileHeader(CBFile $file)
    {
        return '<?php' . $this->cbs->eol . $this->cbs->eol;
    }

    public function buildMainFunction($code)
    {
        return $code;
    }

    public function buildTypeDefinition(CBType $type)
    {
        // We do not declare custom structural types and use "array" for all of them (lists and structs),
        // because this makes the implementation simpler - i.e. no implementation, because then we don't
        // declare custom structural types.
        // However we should have type safety anyway, because that should be ensured by CodeBuilder.
        // Perhaps later we should use objects (StdClass) for structures instead.
        return '';
    }

    public function buildVarUninitializedDeclaration(CBVariable $var)
    {
        return '//'.$this->varName($var);
    }

    public function buildVarInitializedDeclaration(CBBaseVariable $var, CBScope $scope)
    {
        return $this->buildAssignment(
            $this->varName($var)
            , $this->buildVarInitializer($var, $scope));
    }

    public function buildStringVal($string)
    {
        return "'".str_replace("'", "\'", $string)."'";
    }

    public function buildListInitializer(array $list, CBScope $scope)
    {
        $code = 'Array(';
        if (count($list) > 0) {
            $code .= $this->cbs->eol;
            foreach ($list as $v){
                if (is_array($v)) e($v);
                $valCode = $this->buildVal($v, $scope);
                $valCode .= ',' . $this->cbs->eol;

                $valCode = $this->cbs->indent(1, $valCode);

                $code .= $valCode;
            }
        }
        $code .= ')';

        return $code;
    }

    public function buildListIndexAccessor($index)
    {
        return "[".$index."]";
    }

    public function buildListIterator($pathToList, $pathToItem, $pathToIndex = NULL)
    {
        return "foreach ({$pathToList} as " . ($pathToIndex ? "{$pathToIndex} => " : '') . "{$pathToItem})";
    }

    public function buildAddToList($pathToList, $pathToItem)
    {
        return $this->buildAssignment($pathToList.'[]', $pathToItem);
    }

    public function buildMergeLists($pathToFirstList, $pathToSecondList)
    {
        return $this->buildAssignment(
                $pathToFirstList
                , 'array_merge('.$pathToFirstList.', '.$pathToSecondList.')');
    }

    public function buildStructInitializer(array $array, CBScope $scope)
    {
        $code = 'Array(' . $this->cbs->eol;

        foreach ($array as $k => $v)
        {
            $valCode = $this->buildVal($k, $scope);
            $valCode .= " => ";
            $valCode .= $this->buildVal($v, $scope);
            $valCode .= ',' . $this->cbs->eol;

            $valCode = $this->cbs->indent(1, $valCode);

            $code .= $valCode;
        }

        $code .= ')';

        return $code;
    }

    public function buildStructFieldAccessor($fieldName)
    {
        return "['".$fieldName."']";
    }

    public function buildThis()
    {
        return '$this';
    }


    public function varName(CBBaseVariable $var)
    {
        return '$' . $var->name;
    }
}
