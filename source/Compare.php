<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Daniel Popiniuc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace danielgp\info_compare;

/**
 * Description of compare
 *
 * @author Transformer-
 */
class Compare
{

    use \danielgp\common_lib\CommonCode;

    private $localConfiguration;
    private $serverConfiguration;

    public function __construct()
    {
        $this->applicationFlags = [
            'available_languages' => [
                'en_US' => 'EN',
                'ro_RO' => 'RO',
            ],
            'default_language'    => 'ro_RO',
            'name'                => 'Info-Compare'
        ];
        echo $this->setHeaderHtml();
        $this->setDefaultOptions();
        echo $this->setFormOptions();
        if (isset($_GET)) {
            $this->processInfos();
            echo $this->setFormCurlInfos();
            echo $this->setFormInfos();
        }
        echo $this->setFooterHtml();
    }

    private function displayTableFromMultiLevelArray($firstArray, $secondArray)
    {
        global $cfg;
        if ((!is_array($firstArray)) || (!is_array($secondArray))) {
            return '';
        }
        $firstRow     = $this->mergeArraysIntoFirstSecond($firstArray, $secondArray, ['first', 'second']);
        $secondRow    = $this->mergeArraysIntoFirstSecond($secondArray, $firstArray, ['second', 'first']);
        $row          = array_merge($firstRow, $secondRow);
        ksort($row);
        $urlArguments = '?Label=' . $cfg['Defaults']['Label'];
        $sString[]    = '<table style="width:100%">'
            . '<thead><tr>'
            . '<th>Identifier</th>'
            . '<th><a href="' . $cfg['Servers'][$_REQUEST['localConfig']]['url'] . $urlArguments . '" target="_blank">'
            . $cfg['Servers'][$_REQUEST['localConfig']]['name'] . '</a></th>'
            . '<th><a href="' . $cfg['Servers'][$_REQUEST['serverConfig']]['url'] . $urlArguments . '" target="_blank">'
            . $cfg['Servers'][$_REQUEST['serverConfig']]['name'] . '</a></th>'
            . '</tr></thead>'
            . '<tbody>';
        if ($_REQUEST['displayOnlyDifferent'] == '1') {
            $displayOnlyDifferent = true;
        } else {
            $displayOnlyDifferent = false;
        }
        foreach ($row as $key => $value) {
            $rowString = '<tr><td style="width:20%;">' . $key . '</td><td style="width:40%;">'
                . str_replace(',', ', ', $value['first']) . '</td><td style="width:40%;">'
                . str_replace(',', ', ', $value['second']) . '</td></tr>';
            if ($displayOnlyDifferent) {
                if ($value['first'] != $value['second']) {
                    $sString[] = $rowString;
                }
            } else {
                $sString[] = $rowString;
            }
        }
        $sString[] = '</tbody></table>';
        return implode('', $sString);
    }

    private function mergeArraysIntoFirstSecond($firstArray, $secondArray, $pairingSequence = ['first', 'second'])
    {
        $row = [];
        foreach ($firstArray as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    if (is_array($value2)) {
                        foreach ($value2 as $key3 => $value3) {
                            if (is_array($value3)) {
                                foreach ($value3 as $key4 => $value4) {
                                    $keyCrt                            = $key . '_' . $key2 . '__' . $key3 . '__' . $key4;
                                    $row[$keyCrt][$pairingSequence[0]] = $value4;
                                    if (isset($secondArray[$key][$key2][$key3][$key4])) {
                                        $row[$keyCrt][$pairingSequence[1]] = $secondArray[$key][$key2][$key3][$key4];
                                    } else {
                                        $row[$keyCrt][$pairingSequence[1]] = '';
                                    }
                                }
                            } else {
                                $keyCrt                            = $key . '_' . $key2 . '__' . $key3;
                                $row[$keyCrt][$pairingSequence[0]] = $value3;
                                if (isset($secondArray[$key][$key2][$key3])) {
                                    $row[$keyCrt][$pairingSequence[1]] = $secondArray[$key][$key2][$key3];
                                } else {
                                    $row[$keyCrt][$pairingSequence[1]] = '';
                                }
                            }
                        }
                    } else {
                        $keyCrt                            = $key . '_' . $key2;
                        $row[$keyCrt][$pairingSequence[0]] = $value2;
                        if (isset($secondArray[$key][$key2])) {
                            $row[$keyCrt][$pairingSequence[1]] = $secondArray[$key][$key2];
                        } else {
                            $row[$keyCrt][$pairingSequence[1]] = '';
                        }
                    }
                }
            } else {
                $row[$key][$pairingSequence[0]] = $value;
                if (isset($secondArray[$key])) {
                    $row[$key][$pairingSequence[1]] = $secondArray[$key];
                } else {
                    $row[$key][$pairingSequence[1]] = '';
                }
            }
        }
        return $row;
    }

    private function processInfos()
    {
        global $cfg;
        if (isset($_REQUEST['localConfig']) && isset($_REQUEST['serverConfig'])) {
            $urlArguments              = '?Label=' . $cfg['Defaults']['Label'];
            $source                    = $cfg['Servers'][$_REQUEST['localConfig']]['url'] . $urlArguments;
            $this->localConfiguration  = $this->getContentFromUrlThroughCurl($source);
            $destination               = $cfg['Servers'][$_REQUEST['serverConfig']]['url'] . $urlArguments;
            $this->serverConfiguration = $this->getContentFromUrlThroughCurl($destination);
        } else {
            $this->localConfiguration  = ['response' => '', 'info' => ''];
            $this->serverConfiguration = ['response' => '', 'info' => ''];
        }
    }

    /**
     * Converts an array to string
     *
     * @param string $sSeparator
     * @param array $aElements
     * @return string
     */
    private function setArray2String4Url($sSeparator, $aElements, $aExceptedElements = [''])
    {
        if (!is_array($aElements)) {
            return '';
        }
        $sReturn = [];
        reset($aElements);
        foreach ($aElements as $key => $value) {
            if (!in_array($key, $aExceptedElements)) {
                if (is_array($aElements[$key])) {
                    $aCounter = count($aElements[$key]);
                    for ($counter2 = 0; $counter2 < $aCounter; $counter2++) {
                        if ($value[$counter2] != '') {
                            $sReturn[] = $key . '[]=' . $value[$counter2];
                        }
                    }
                } else {
                    if ($value != '') {
                        $sReturn[] = $key . '=' . $value;
                    }
                }
            }
        }
        return implode($sSeparator, $sReturn);
    }

    private function setDefaultOptions()
    {
        global $cfg;
        if (!isset($_REQUEST['displayOnlyDifferent'])) {
            $_REQUEST['displayOnlyDifferent'] = '1';
        }
        if (!isset($_REQUEST['localConfig'])) {
            $_REQUEST['localConfig'] = $cfg['Defaults']['Source'];
        }
        if (!isset($_REQUEST['serverConfig'])) {
            $_REQUEST['serverConfig'] = $cfg['Defaults']['Target'];
        }
    }

    private function setFooterHtml()
    {
        $sReturn   = [];
        $sReturn[] = '</div><!-- from main Tabber -->';
        $sReturn[] = '<div class="resetOnly author">&copy; 2015 Daniel Popiniuc</div>';
        $sReturn[] = '<hr/>';
        $sReturn[] = '<div class="disclaimer">'
            . 'The developer cannot be liable of any data input or results, '
            . 'included but not limited to any implication of these '
            . '(anywhere and whomever there might be these)!'
            . '</div>';
        $sReturn[] = '</body>';
        $sReturn[] = '</html>';
        return implode('', $sReturn);
    }

    private function setFormCurlInfos()
    {
        $source      = $this->localConfiguration['info'];
        $destination = $this->serverConfiguration['info'];
        return '<div class="tabbertab" id="tabCurl" title="CURL infos">'
            . $this->displayTableFromMultiLevelArray($source, $destination)
            . '</div><!--from tabCurl-->';
    }

    private function setFormInfos()
    {
        $source      = $this->setJson2array($this->localConfiguration['response']);
        $destination = $this->setJson2array($this->serverConfiguration['response']);
        return '<div class="tabbertab" id="tabConfigs" title="Informations">'
            . $this->displayTableFromMultiLevelArray($source, $destination)
            . '</div><!--from tabConfigs-->';
    }

    private function setFormOptions()
    {
        global $cfg;
        $sReturn    = [];
        $sReturn[]  = '<fieldset style="float:left;">'
            . '<legend>Type of results to be displayed</legend>'
            . '<input type="radio" name="displayOnlyDifferent" id="displayOnlyDifferent" value="1" '
            . ($_REQUEST['displayOnlyDifferent'] == '1' ? 'checked ' : '')
            . '/><label for="displayOnlyDifferent">Only the Different values</label>'
            . '<br/>'
            . '<input type="radio" name="displayOnlyDifferent" id="displayAll" value="0" '
            . ($_REQUEST['displayOnlyDifferent'] == '0' ? 'checked ' : '')
            . '/><label for="displayAll">All</label>'
            . '</fieldset>';
        $tmpOptions = [];
        foreach ($cfg['Servers'] as $key => $value) {
            $tmpOptions[] = '<a href="' . $value['url'] . '" target="_blank">run-me</a>&nbsp;'
                . '<input type="radio" name="localConfig" id="localConfig_'
                . $key . '" value="' . $key . '" '
                . ($_REQUEST['localConfig'] == $key ? 'checked ' : '')
                . '/><label for="localConfig_' . $key . '">'
                . $value['name'] . '</label>';
        }
        $sReturn[]  = '<fieldset style="float:left;">'
            . '<legend>List of source configuration providers</legend>'
            . implode('<br/>', $tmpOptions)
            . '</fieldset>';
        unset($tmpOptions);
        $tmpOptions = [];
        foreach ($cfg['Servers'] as $key => $value) {
            $tmpOptions[] = '<a href="' . $value['url'] . '" target="_blank">run-me</a>&nbsp;'
                . '<input type="radio" name="serverConfig" id="serverConfig_'
                . $key . '" value="' . $key . '" '
                . ($_REQUEST['serverConfig'] == $key ? 'checked ' : '')
                . '/><label for="serverConfig_' . $key . '">'
                . $value['name'] . '</label>';
        }
        $sReturn[] = '<fieldset style="float:left;">'
            . '<legend>List of target configuration providers</legend>'
            . implode('<br/>', $tmpOptions) . '</fieldset>';
        return '<div class="tabbertab'
            . ((!isset($_REQUEST['localConfig']) && !isset($_REQUEST['serverConfig'])) ? ' tabbertabtabdefault' : '')
            . '" id="tabOptions" title="Options">'
            . '<style>label { width: auto; }</style>'
            . '<form method="get" action="' . $_SERVER['PHP_SELF'] . '"><input type="submit" value="Apply" /><br/>' . implode('', $sReturn) . '</form>'
            . $this->setClearBoth1px()
            . '</div><!--from tabOptions-->';
    }

    private function setHeaderHtml()
    {
        return '<!DOCTYPE html>'
            . '<html lang="en-US">'
            . '<head>'
            . '<meta charset="utf-8" />'
            . '<meta name="viewport" content="width=device-width" />'
            . '<title>' . $this->applicationFlags['name'] . '</title>'
            . $this->setCssFile('css/main.css')
            . $this->setJavascriptFile('js/tabber.min.js')
            . '</head>'
            . '<body>'
            . $this->setJavascriptContent('document.write(\'<style type="text/css">.tabber{display:none;}</style>\');')
            . '<h1>' . $this->applicationFlags['name'] . '</h1>'
            . '<div class="tabber" id="tab">'
        ;
    }
}
