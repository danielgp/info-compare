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

    private function buildKey($inArray)
    {
        $aReturn = [];
        foreach ($inArray as $key => $val) {
            $aReturn[] = str_repeat('_', $key) . $val;
        }
        return implode('', $aReturn);
    }

    private function emptyIfNotSet($inValue)
    {
        if (isset($inValue)) {
            $sReturn = $inValue;
        } else {
            $sReturn = '';
        }
        return $sReturn;
    }

    protected function mergeArraysIntoFirstSecond($stAry, $ndAry, $pSq = ['first', 'second'])
    {
        $row = [];
        foreach ($stAry as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $ky2 => $vl2) {
                    if (is_array($vl2)) {
                        foreach ($vl2 as $ky3 => $vl3) {
                            if (is_array($vl3)) {
                                foreach ($vl3 as $ky4 => $vl4) {
                                    $row[$this->buildKey([$key, $ky2, $ky3, $ky4])] = [
                                        $pSq[0] => $vl4,
                                        $pSq[1] => $this->emptyIfNotSet($ndAry[$key][$ky2][$ky3][$ky4]),
                                    ];
                                }
                            } else {
                                $row[$this->buildKey([$key, $ky2, $ky3])] = [
                                    $pSq[0] => $vl3,
                                    $pSq[1] => $this->emptyIfNotSet($ndAry[$key][$ky2][$ky3]),
                                ];
                            }
                        }
                    } else {
                        $row[$this->buildKey([$key, $ky2])] = [
                            $pSq[0] => $vl2,
                            $pSq[1] => $this->emptyIfNotSet($ndAry[$key][$ky2]),
                        ];
                    }
                }
            } else {
                $row[$this->buildKey([$key])] = [$pSq[0] => $val, $pSq[1] => $this->emptyIfNotSet($ndAry[$key])];
            }
        }
        return $row;
    }
}
