<?php

/**
 * Displays a visual string diff, using phpspec/php-diff library
 * @param $str1
 * @param $str2
 */
function string_diff($str1, $str2)
{
    // Don't display visual string diff in text mode
    if (php_sapi_name() == 'cli') {
        return;
    }

    static $css_rendered = FALSE;

    $a = explode("\n", $str1);
    $b = explode("\n", $str2);

    // Options for generating the diff
    $options = array(
        //'ignoreWhitespace' => true,
        //'ignoreCase' => true,
    );

    // Initialize the diff class
    $diff = new Diff($a, $b, $options);

    // Generate a side by side diff
    $renderer = new Diff_Renderer_Html_SideBySide;
    echo $diff->Render($renderer);

    if ($css_rendered == FALSE) {
        echo string_diff_css();
        $css_rendered = TRUE;
    }
}

function string_diff_css()
{
    $css = <<<EOD
<style>
.Differences {
	width: 100%;
	border-collapse: collapse;
	border-spacing: 0;
	empty-cells: show;
}

.Differences thead th {
	text-align: left;
	border-bottom: 1px solid #000;
	background: #aaa;
	color: #000;
	padding: 4px;
}
.Differences tbody th {
	text-align: right;
	background: #ccc;
	width: 4em;
	padding: 1px 2px;
	border-right: 1px solid #000;
	vertical-align: top;
	font-size: 13px;
}

.Differences td {
	padding: 1px 2px;
	font-family: Consolas, monospace;
	font-size: 13px;
}

.DifferencesSideBySide .ChangeInsert td.Left {
	background: #dfd;
}

.DifferencesSideBySide .ChangeInsert td.Right {
	background: #cfc;
}

.DifferencesSideBySide .ChangeDelete td.Left {
	background: #f88;
}

.DifferencesSideBySide .ChangeDelete td.Right {
	background: #faa;
}

.DifferencesSideBySide .ChangeReplace .Left {
	background: #fe9;
}

.DifferencesSideBySide .ChangeReplace .Right {
	background: #fd8;
}

.Differences ins, .Differences del {
	text-decoration: none;
}

.DifferencesSideBySide .ChangeReplace ins, .DifferencesSideBySide .ChangeReplace del {
	background: #fc0;
}

.Differences .Skipped {
	background: #f7f7f7;
}

.DifferencesInline .ChangeReplace .Left,
.DifferencesInline .ChangeDelete .Left {
	background: #fdd;
}

.DifferencesInline .ChangeReplace .Right,
.DifferencesInline .ChangeInsert .Right {
	background: #dfd;
}

.DifferencesInline .ChangeReplace ins {
	background: #9e9;
}

.DifferencesInline .ChangeReplace del {
	background: #e99;
}
</style>
EOD;

    return $css;
}

/**
 * Tests if two arrays are identical.
 * Arrays are identical if all their values are equal and type equal (===) (recursively).
 * Also, the arrays must contain the same values (all values present in $a1
 * must also be present in $a2 and vice-versa).
 *
 * NOTE: that, however the order of array elements is not checked for (it can be different,
 * and the arays will still be considered identical).
 *
 * IMPORTANT: object comparison is only available on PHP 5+, and it only compares object
 * public properties. On earlier versions, i'm guessing it will always return TRUE for
 * objects, that have the same property names (without looking at the values of those
 * properties).
 */
function arrays_identical($a1, $a2, $nonStrictEquality = FALSE)
{
    $isObject = is_object($a1);
    $isObject2 = is_object($a2);
    if ($isObject != $isObject2)
        return FALSE;

    if ($isObject)
    {
        $props1 = array_keys(get_object_vars($a1));
        $props2 = array_keys(get_object_vars($a2));
        $diff1 = array_diff($props1, $props2);
        $diff2 = array_diff($props2, $props1);
        if (count($diff1) > 0 OR count($diff2) > 0)
            return FALSE;
    }

    foreach ($a1 as $k => $v)
    {
        if ($isObject)
        {
            if (! property_exists($a2, $k))
                return FALSE;

            if (is_array($a1->$k) AND ! is_array($a2->$k))
                return FALSE;
            if (! is_array($a1->$k) AND is_array($a2->$k))
                return FALSE;
            if (is_array($a1->$k) AND is_array($a2->$k))
            {
                if (arrays_identical($a1->$k, $a2->$k, $nonStrictEquality) == FALSE)
                    return FALSE;

                continue;
            }

            if (is_object($a1->$k) AND ! is_object($a2->$k))
                return FALSE;
            if (! is_object($a1->$k) AND is_object($a2->$k))
                return FALSE;
            if (is_object($a1->$k) AND is_object($a2->$k))
            {
                if (arrays_identical($a1->$k, $a2->$k, $nonStrictEquality) == FALSE)
                    return FALSE;

                continue;
            }

            if ($nonStrictEquality)
            {
                if ($a1->$k != $a2->$k)
                    return FALSE;
            }
            else
            {
                if ($a1->$k !== $a2->$k)
                    return FALSE;
            }
        }
        else // if ($isObject)
        {
            if (! array_key_exists($k, $a2))
                return FALSE;
            if (is_array($a1[$k]) AND ! is_array($a2[$k]))
                return FALSE;
            if (! is_array($a1[$k]) AND is_array($a2[$k]))
                return FALSE;
            if (is_array($a1[$k]) AND is_array($a2[$k]))
            {
                if (arrays_identical($a1[$k], $a2[$k], $nonStrictEquality) == FALSE)
                    return FALSE;

                unset($a2[$k]);
                continue;
            }

            if (is_object($a1[$k]) AND ! is_object($a2[$k]))
                return FALSE;
            if (! is_object($a1[$k]) AND is_object($a2[$k]))
                return FALSE;
            if (is_object($a1[$k]) AND is_object($a2[$k]))
            {
                if (arrays_identical($a1[$k], $a2[$k], $nonStrictEquality) == FALSE)
                    return FALSE;

                unset($a2[$k]);
                continue;
            }

            if ($nonStrictEquality)
            {
                if ($a1[$k] != $a2[$k])
                    return FALSE;
            }
            else
            {
                if ($a1[$k] !== $a2[$k])
                    return FALSE;
            }

            unset($a2[$k]);
        }
    }

    if ($isObject == FALSE AND count($a2) > 0)
        return FALSE;

    return TRUE;
}
