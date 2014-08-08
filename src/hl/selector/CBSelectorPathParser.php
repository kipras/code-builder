<?php
class CBSelectorPathParser extends CBEntity
{
    /**
     * @param string $path
     * @return CBSelectorToken[]|null
     */
    public function parse($path)
    {
        $selectorTokenList = $this->getTokensFromSelector($path);

        $r = Array();
        $tokenIdx = -1;
        $tokenListLength = count($selectorTokenList);
        if ($tokenListLength == 0) {
            $error = Array(
                'errorText' => 'selectorPath must not be empty',
                'errorPos' => 0,
            );
            return $this->showParseError($path, $error);
        }

        while ($tokenIdx < $tokenListLength - 1) {
            $parseNextPartRes = $this->parseNextPart($selectorTokenList, $tokenIdx);
            if (FALSE == $parseNextPartRes['success']) {
                return $this->showParseError($path, $parseNextPartRes);
            }

            $r[] = $parseNextPartRes['part'];
        }

        return $r;
    }

    private function showParseError($selector, array $parseNextPartRes)
    {
        $this->_cbs->error(
            'Error: "'.$parseNextPartRes['errorText'].'", at col '.$parseNextPartRes['errorPos'].' : "'
            .substr($selector, $parseNextPartRes['errorPos'], 10).'".'
            .' Full selector: "'.$selector.'"'
            , "Selector parse error");
        return NULL;
    }

    private function parseNextPart(array $selectorTokenList, &$tokenIdx)
    {
        $r = Array(
            'success' => TRUE,
            'tokenIdx' => $tokenIdx,
            'part' => NULL,
            'errorText' => NULL,
            'errorPos' => NULL,
        );

        $nextToken = $this->takeNextToken($selectorTokenList, $tokenIdx);
        if ($nextToken['token'] == '.') {
            $nextToken = $this->takeNextToken($selectorTokenList, $tokenIdx);
            if ($nextToken === NULL) {
                return $this->parseNextPartError($r, "Unexpected selector end", $selectorTokenList, $tokenIdx);
            } else if ($nextToken['token'] == '.' OR $nextToken['token'] == '[]') {
                return $this->parseNextPartError($r, "Expected field name", $selectorTokenList, $tokenIdx);
            }

            $f = new CBSelectorTokenField();
            $f->name = $nextToken['token'];
            $r['part'] = $f;

        } else if ($nextToken['token'] == '[]') {
            $lookaheadToken = $this->lookaheadNextToken($selectorTokenList, $tokenIdx);
            if ($lookaheadToken !== NULL AND
                FALSE == ($lookaheadToken['token'] == '.' OR $lookaheadToken['token'] == '[]'))
            {
                return $this->parseNextPartError($r, "Unexpected identifier", $selectorTokenList, $tokenIdx);
            }

            $l = new CBSelectorTokenList();
            $r['part'] = $l;
        } else {
            return $this->parseNextPartError($r, "A selector part should begin with . or []", $selectorTokenList, $tokenIdx);
        }

        return $r;
    }

    private function parseNextPartError($r, $errorText, $selectorTokenList, $tokenIdx)
    {
        $r['success'] = FALSE;
        $r['errorText'] = $errorText;
        $r['errorPos'] = $this->getTokenPos($selectorTokenList, $tokenIdx);

        return $r;
    }

    /**
     * Increses token index and returns the token that the index then points at
     * @param array $selectorTokenList
     * @param $tokenIdx
     * @return array|null
     */
    private function takeNextToken(array $selectorTokenList, &$tokenIdx)
    {
        $tokenIdx++;
        return $this->getToken($selectorTokenList, $tokenIdx);
    }

    /**
     * Similar to takeNextToken() but does not modify token index. Useful to just check what the next
     * token is, for validation purposes.
     */
    private function lookaheadNextToken(array $selectorTokenList, $tokenIdx)
    {
        $tokenIdx++;
        return $this->getToken($selectorTokenList, $tokenIdx);
    }

    private function getToken($selectorTokenList, $tokenIdx)
    {
        if ($tokenIdx >= count($selectorTokenList)) {
            return NULL;
        }

        $r = $selectorTokenList[$tokenIdx];
        return $r;
    }

    private function getTokenPos($selectorTokenList, $tokenIdx)
    {
        if ($tokenIdx >= count($selectorTokenList)) {
            $token = end($selectorTokenList);
            return $token['posAfter'];
        } else {
            $token = $selectorTokenList[$tokenIdx];
            return $token['pos'];
        }
    }

    /**
     * Splits selector into tokens
     * @param string $selector
     * @return array A list of tokens
     */
    private function getTokensFromSelector($selector)
    {
        $structFieldDelims = $this->findDelimiterPositions('.', $selector);
        $listDelims = $this->findDelimiterPositions('[]', $selector);

        $allDelims = array_merge($structFieldDelims, $listDelims);
        usort($allDelims, Array($this, 'sortDelims'));

        $r = Array();
        $lastPos = 0;
        foreach ($allDelims as $delim) {
            // Add identifier token
            if ($delim['pos'] > $lastPos) {
                $r[] = Array(
                    'token' => substr($selector, $lastPos, $delim['pos'] - $lastPos),
                    'pos' => $lastPos,
                    'posAfter' => $delim['pos'],
                );
            }

            // Add delimiter token
            $r[] = $delim;

            $lastPos = $delim['posAfter'];
        }
        if ($lastPos < strlen($selector)) {
            $r[] = Array(
                'token' => substr($selector, $lastPos),
                'pos' => $lastPos,
                'posAfter' => strlen($selector),
            );
        }

        return $r;
    }

    private function findDelimiterPositions($delimiter, $string)
    {
        $r = Array();
        $offset = 0;
        $delimLength = strlen($delimiter);
        while (($pos = strpos($string, $delimiter, $offset)) !== FALSE) {
            $posAfter = $pos + $delimLength;
            $r[] = Array(
                'pos' => $pos,
                'posAfter' => $posAfter,
                'token' => $delimiter,
            );
            $offset = $posAfter;
        }
        return $r;
    }

    private function sortDelims($d1, $d2)
    {
        return $d1['pos'] - $d2['pos'];
    }
}
