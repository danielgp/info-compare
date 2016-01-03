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

trait CompareTable
{

    use CompareResults;

    private $superGlobals;

    protected function displayTableFromMultiLevelArray($inArray)
    {
        if ((!is_array($inArray['source'])) || (!is_array($inArray['destination']))) {
            return '';
        }
        $this->superGlobals = $inArray['SuperGlobals'];
        return '<table style="width:100%">'
                . $this->tableHeader(['Servers' => $inArray['Servers']])
                . $this->tableBody([
                    'source'      => $inArray['source'],
                    'destination' => $inArray['destination'],
                ])
                . '</table>';
    }

    private function decideDisplayAllOrOnlyDifferent()
    {
        $displayOnlyDifferent = false;
        if ($this->superGlobals->get('displayOnlyDifferent') == '1') {
            $displayOnlyDifferent = true;
        }
        return $displayOnlyDifferent;
    }

    private function displayTableRow($inArray)
    {
        $sString = '';
        if ($inArray['displayOnlyDifferent']) {
            if ($inArray['first'] != $inArray['second']) {
                $sString = $inArray['rowContent'];
            }
        } else {
            $sString = $inArray['rowContent'];
        }
        return $sString;
    }

    private function prepareArrayForTableBody($inArray)
    {
        $source    = $inArray['source'];
        $dest      = $inArray['destination'];
        $firstRow  = $this->mergeArraysIntoFirstSecond($source, $dest, ['first', 'second']);
        $secondRow = $this->mergeArraysIntoFirstSecond($dest, $source, ['second', 'first']);
        $row       = array_merge($firstRow, $secondRow);
        ksort($row);
        return $row;
    }

    private function tableBody($inArray)
    {
        $row                  = $this->prepareArrayForTableBody($inArray);
        $displayOnlyDifferent = $this->decideDisplayAllOrOnlyDifferent();
        $aString              = [];
        foreach ($row as $key => $value) {
            $rowString = '<tr><td style="width:20%;">' . $key . '</td><td style="width:40%;">'
                    . str_replace(',', ', ', $value['first']) . '</td><td style="width:40%;">'
                    . str_replace(',', ', ', $value['second']) . '</td></tr>';
            $aString[] = $this->displayTableRow([
                'rowContent'           => $rowString,
                'displayOnlyDifferent' => $displayOnlyDifferent,
                'first'                => $value['first'],
                'second'               => $value['second'],
            ]);
        }
        return '<tbody>' . implode('', $aString) . '</tbody>';
    }

    private function tableHeader($inArray)
    {
        $urlArguments = '?Label=' . $this->superGlobals->get('Label');
        return '<thead><tr>'
                . '<th>Identifier</th>'
                . '<th><a href="' . $inArray['Servers'][$this->superGlobals->get('localConfig')]['url']
                . $urlArguments . '" target="_blank">'
                . $inArray['Servers'][$this->superGlobals->get('localConfig')]['name'] . '</a></th>'
                . '<th><a href="' . $inArray['Servers'][$this->superGlobals->get('serverConfig')]['url']
                . $urlArguments . '" target="_blank">'
                . $inArray['Servers'][$this->superGlobals->get('serverConfig')]['name'] . '</a></th>'
                . '</tr></thead>';
    }
}
