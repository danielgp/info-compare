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

    private $informatorKnownLabels;
    private $localConfiguration;
    private $serverConfiguration;
    private $config;

    public function __construct()
    {
        $this->getConfiguration();
        $this->applicationFlags      = [
            'available_languages' => [
                'en_US' => 'EN',
                'ro_RO' => 'RO',
            ],
            'default_language'    => 'ro_RO',
            'name'                => 'Info-Compare'
        ];
        $urlToGetLbl                 = $this->config['Servers'][$this->config['Defaults']['Source']]['url']
            . '?Label=---' . urlencode(' List of known labels');
        $knownLabels                 = $this->getContentFromUrlThroughCurlAsArrayIfJson($urlToGetLbl)['response'];
        $this->informatorKnownLabels = array_diff($knownLabels, ['--- List of known labels']);
        echo $this->setHeaderHtml();
        $this->setDefaultOptions();
        echo $this->setFormOptions();
        if (isset($_GET['Label'])) {
            $this->processInfos();
            echo $this->setFormCurlInfos();
            echo $this->setFormInfos();
        }
        echo $this->setFooterHtml();
    }

    private function displayTableFromMultiLevelArray($firstArray, $secondArray)
    {
        if ((!is_array($firstArray)) || (!is_array($secondArray))) {
            return '';
        }
        $firstRow     = $this->mergeArraysIntoFirstSecond($firstArray, $secondArray, ['first', 'second']);
        $secondRow    = $this->mergeArraysIntoFirstSecond($secondArray, $firstArray, ['second', 'first']);
        $row          = array_merge($firstRow, $secondRow);
        ksort($row);
        $urlArguments = '?Label=' . $_REQUEST['Label'];
        $sString[]    = '<table style="width:100%">'
            . '<thead><tr>'
            . '<th>Identifier</th>'
            . '<th><a href="' . $this->config['Servers'][$_REQUEST['localConfig']]['url']
            . $urlArguments . '" target="_blank">'
            . $this->config['Servers'][$_REQUEST['localConfig']]['name'] . '</a></th>'
            . '<th><a href="' . $this->config['Servers'][$_REQUEST['serverConfig']]['url']
            . $urlArguments . '" target="_blank">'
            . $this->config['Servers'][$_REQUEST['serverConfig']]['name'] . '</a></th>'
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

    private function getConfiguration()
    {
        $servers = explode('|', IC_SERVERS);
        foreach ($servers as $value) {
            $pieces                    = explode('=', $value);
            $this->config['Servers'][] = [
                'name' => $pieces[0],
                'url'  => $pieces[1],
            ];
        }
        $serverNames                        = array_column($this->config['Servers'], 'name');
        $this->config['Defaults']['Source'] = array_search(IC_SOURCE, $serverNames);
        $this->config['Defaults']['Target'] = array_search(IC_TARGET, $serverNames);
        $this->config['Defaults']['Label']  = IC_LABEL;
    }

    private function mergeArraysIntoFirstSecond($firstArray, $secondArray, $pSequence = ['first', 'second'])
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
                                    if (isset($secondArray[$key][$key2][$key3][$key4])) {
                                        $row[$keyCrt][$pSequence[1]] = $secondArray[$key][$key2][$key3][$key4];
                                    } else {
                                        $row[$keyCrt][$pSequence[1]] = '';
                                    }
                                }
                            } else {
                                $keyCrt                      = $key . '_' . $key2 . '__' . $key3;
                                $row[$keyCrt][$pSequence[0]] = $value3;
                                if (isset($secondArray[$key][$key2][$key3])) {
                                    $row[$keyCrt][$pSequence[1]] = $secondArray[$key][$key2][$key3];
                                } else {
                                    $row[$keyCrt][$pSequence[1]] = '';
                                }
                            }
                        }
                    } else {
                        $keyCrt                      = $key . '_' . $key2;
                        $row[$keyCrt][$pSequence[0]] = $value2;
                        if (isset($secondArray[$key][$key2])) {
                            $row[$keyCrt][$pSequence[1]] = $secondArray[$key][$key2];
                        } else {
                            $row[$keyCrt][$pSequence[1]] = '';
                        }
                    }
                }
            } else {
                $row[$key][$pSequence[0]] = $value;
                if (isset($secondArray[$key])) {
                    $row[$key][$pSequence[1]] = $secondArray[$key];
                } else {
                    $row[$key][$pSequence[1]] = '';
                }
            }
        }
        return $row;
    }

    private function processInfos()
    {
        if (isset($_REQUEST['localConfig']) && isset($_REQUEST['serverConfig'])) {
            $urlArguments              = '?Label=' . urlencode($_REQUEST['Label']);
            $source                    = $this->config['Servers'][$_REQUEST['localConfig']]['url'] . $urlArguments;
            $this->localConfiguration  = $this->getContentFromUrlThroughCurlAsArrayIfJson($source);
            $destination               = $this->config['Servers'][$_REQUEST['serverConfig']]['url'] . $urlArguments;
            $this->serverConfiguration = $this->getContentFromUrlThroughCurlAsArrayIfJson($destination);
        } else {
            $this->localConfiguration  = ['response' => '', 'info' => ''];
            $this->serverConfiguration = ['response' => '', 'info' => ''];
        }
    }

    private function setDefaultOptions()
    {
        if (!isset($_REQUEST['displayOnlyDifferent'])) {
            $_REQUEST['displayOnlyDifferent'] = '1';
        }
        if (!isset($_REQUEST['localConfig'])) {
            $_REQUEST['localConfig'] = $this->config['Defaults']['Source'];
        }
        if (!isset($_REQUEST['serverConfig'])) {
            $_REQUEST['serverConfig'] = $this->config['Defaults']['Target'];
        }
        if (!isset($_REQUEST['Label'])) {
            $_REQUEST['Label'] = $this->config['Defaults']['Label'];
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
        return $this->setFooterCommon(implode('', $sReturn));
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
        $source      = $this->localConfiguration['response'];
        $destination = $this->serverConfiguration['response'];
        return '<div class="tabbertab'
            . (isset($_GET['Label']) ? ' tabbertabdefault' : '')
            . '" id="tabConfigs" title="Informations">'
            . $this->displayTableFromMultiLevelArray($source, $destination)
            . '</div><!--from tabConfigs-->';
    }

    private function setFormOptions()
    {
        $sReturn    = [];
        $sReturn[]  = '<fieldset style="float:left;">'
            . '<legend>Type of results displayed</legend>'
            . '<input type="radio" name="displayOnlyDifferent" id="displayOnlyDifferent" value="1" '
            . ($_REQUEST['displayOnlyDifferent'] == '1' ? 'checked ' : '') . '/>'
            . '<label for="displayOnlyDifferent">Only the Different values</label>'
            . '<br/>'
            . '<input type="radio" name="displayOnlyDifferent" id="displayAll" value="0" '
            . ($_REQUEST['displayOnlyDifferent'] == '0' ? 'checked ' : '') . '/>'
            . '<label for="displayAll">All</label>'
            . '</fieldset>';
        $tmpOptions = [];
        foreach ($this->config['Servers'] as $key => $value) {
            $tmpOptions[] = '<a href="' . $value['url'] . '" target="_blank">run-me</a>&nbsp;'
                . '<input type="radio" name="localConfig" id="localConfig_'
                . $key . '" value="' . $key . '" '
                . ($_REQUEST['localConfig'] == $key ? 'checked ' : '')
                . '/><label for="localConfig_' . $key . '">'
                . $value['name'] . '</label>';
        }
        $sReturn[]  = '<fieldset style="float:left;">'
            . '<legend>Source configuration providers</legend>'
            . implode('<br/>', $tmpOptions)
            . '</fieldset>';
        unset($tmpOptions);
        $tmpOptions = [];
        foreach ($this->config['Servers'] as $key => $value) {
            $tmpOptions[] = '<a href="' . $value['url'] . '" target="_blank">run-me</a>&nbsp;'
                . '<input type="radio" name="serverConfig" id="serverConfig_'
                . $key . '" value="' . $key . '" ' . ($_REQUEST['serverConfig'] == $key ? 'checked ' : '') . '/>'
                . '<label for="serverConfig_' . $key . '">' . $value['name'] . '</label>';
        }
        $sReturn[]  = '<fieldset style="float:left;">'
            . '<legend>Target configuration providers</legend>'
            . implode('<br/>', $tmpOptions)
            . '</fieldset>';
        unset($tmpOptions);
        $tmpOptions = [];
        foreach ($this->informatorKnownLabels as $value) {
            $tmpOptions[] = '<input type="radio" name="Label" id="Label_' . $value . '" '
                . 'value="' . $value . '" ' . ($_REQUEST['Label'] == $value ? 'checked ' : '') . '/>'
                . '<label for="Label_' . $value . '">' . $value . '</label>';
        }
        $sReturn[] = '<fieldset style="float:left;">'
            . '<legend>Informator Label to use</legend>'
            . implode('<br/>', $tmpOptions)
            . '</fieldset>';
        return '<div class="tabbertab'
            . ((!isset($_REQUEST['localConfig']) && !isset($_REQUEST['serverConfig'])) ? ' tabbertabtabdefault' : '')
            . '" id="tabOptions" title="Options">'
            . '<style type="text/css" media="all" scoped>label { width: auto; }</style>'
            . '<form method="get" action="' . $_SERVER['PHP_SELF'] . '">'
            . '<input type="submit" value="Apply" />'
            . '<br/>' . implode('', $sReturn)
            . '</form>'
            . $this->setClearBoth1px()
            . '</div><!--from tabOptions-->';
    }

    private function setHeaderHtml()
    {
        return $this->setHeaderCommon([
                'lang'       => 'en-US',
                'title'      => $this->applicationFlags['name'],
                'css'        => 'css/main.css',
                'javascript' => 'js/tabber.min.js',
            ])
            . $this->setJavascriptContent('document.write(\'<style type="text/css">.tabber{display:none;}</style>\');')
            . '<h1>' . $this->applicationFlags['name'] . '</h1>'
            . '<div class="tabber" id="tab">'
        ;
    }
}
