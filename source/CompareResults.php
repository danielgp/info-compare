<?php

/*
 * The MIT License
 *
 * Copyright 2016 Daniel Popiniuc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace danielgp\info_compare;

trait CompareResults
{

    private function decideToReturn($inValue)
    {
        if (isset($inValue)) {
            $sReturn = $inValue;
        } else {
            $sReturn = '';
        }
        return $sReturn;
    }

    protected function mergeArraysIntoFirstSecond($firstArray, $secondArray, $pSequence = ['first', 'second'])
    {
        $row = [];
        foreach ($firstArray as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    if (is_array($value2)) {
                        foreach ($value2 as $key3 => $value3) {
                            if (is_array($value3)) {
                                foreach ($value3 as $key4 => $value4) {
                                    $keyCrt                      = $key . '_' . $key2 . '__' . $key3 . '__' . $key4;
                                    $row[$keyCrt][$pSequence[0]] = $value4;
                                    $toEval                      = $secondArray[$key][$key2][$key3][$key4];
                                    $row[$keyCrt][$pSequence[1]] = $this->decideToReturn($toEval);
                                }
                            } else {
                                $keyCrt                      = $key . '_' . $key2 . '__' . $key3;
                                $row[$keyCrt][$pSequence[0]] = $value3;
                                $row[$keyCrt][$pSequence[1]] = $this->decideToReturn($secondArray[$key][$key2][$key3]);
                            }
                        }
                    } else {
                        $keyCrt                      = $key . '_' . $key2;
                        $row[$keyCrt][$pSequence[0]] = $value2;
                        $row[$keyCrt][$pSequence[1]] = $this->decideToReturn($secondArray[$key][$key2]);
                    }
                }
            } else {
                $row[$key][$pSequence[0]] = $value;
                $row[$key][$pSequence[1]] = $this->decideToReturn($secondArray[$key]);
            }
        }
        return $row;
    }
}
