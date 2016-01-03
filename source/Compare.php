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

    use \danielgp\common_lib\CommonCode,
        \danielgp\info_compare\ConfigurationCompare,
        \danielgp\info_compare\OutputFormBuilder,
        \danielgp\info_compare\CompareTable;

    private $config;
    private $localConfiguration;
    private $serverConfiguration;

    public function __construct()
    {
        $this->getConfiguration();
        echo $this->setHeaderHtml();
        $rqst         = new \Symfony\Component\HttpFoundation\Request;
        $superGlobals = $rqst->createFromGlobals();
        $this->prepareForOutputForm(['SuperGlobals' => $superGlobals]);
        if (!is_null($superGlobals->get('Label'))) {
            $this->processInfos(['sGlobals' => $superGlobals]);
            echo $this->setFormCurlInfos(['SuperGlobals' => $superGlobals]);
            echo $this->setFormInfos(['SuperGlobals' => $superGlobals]);
        }
        echo $this->setFooterHtml();
    }

    private function getConfiguration()
    {
        $strdConfig = $this->configuredDeployedInformators();
        foreach ($strdConfig['informators'] as $key => $value) {
            $this->config['Servers'][] = [
                'name' => $key,
                'url'  => $value,
            ];
        }
        $haystack                                         = array_keys($strdConfig['informators']);
        $this->config['Defaults']['Label']                = $strdConfig['default']['label'];
        $this->config['Defaults']['localConfig']          = array_search($strdConfig['default']['source'], $haystack);
        $this->config['Defaults']['serverConfig']         = array_search($strdConfig['default']['target'], $haystack);
        $this->config['Defaults']['displayOnlyDifferent'] = $strdConfig['default']['typeOfResults'];
    }

    private function prepareForOutputForm($inArray)
    {
        $urlToGetLbl = $this->config['Servers'][$this->config['Defaults']['localConfig']]['url']
                . '?Label=---' . urlencode(' List of known labels');
        $knownLabels = $this->getContentFromUrlThroughCurlAsArrayIfJson($urlToGetLbl)['response'];
        echo $this->setOutputForm([
            'Defaults'     => $this->config['Defaults'],
            'KnownLabels'  => $knownLabels,
            'Servers'      => $this->config['Servers'],
            'SuperGlobals' => $inArray['SuperGlobals'],
        ]);
    }

    private function processInfos($inArray)
    {
        if (!is_null($inArray['sGlobals']->get('localConfig')) && !is_null($inArray['sGlobals']->get('serverConfig'))) {
            $urlArguments              = '?Label=' . urlencode($inArray['sGlobals']->get('Label'));
            $source                    = $this->config['Servers'][$inArray['sGlobals']->get('localConfig')]['url']
                    . $urlArguments;
            $this->localConfiguration  = $this->getContentFromUrlThroughCurlAsArrayIfJson($source);
            $destination               = $this->config['Servers'][$inArray['sGlobals']->get('serverConfig')]['url']
                    . $urlArguments;
            $this->serverConfiguration = $this->getContentFromUrlThroughCurlAsArrayIfJson($destination);
        } else {
            $this->localConfiguration  = ['response' => '', 'info' => ''];
            $this->serverConfiguration = ['response' => '', 'info' => ''];
        }
    }

    private function setFooterHtml()
    {
        $footerToInject = [
            '</div><!-- from main Tabber -->',
            '<div class="resetOnly author">&copy; 2015 Daniel Popiniuc</div>',
            '<hr/><div class="disclaimer">The developer cannot be liable of any data input or results, ',
            'included but not limited to any implication of these (anywhere and whomever there might be these)!',
            '</div>',
        ];
        return $this->setFooterCommon($footerToInject);
    }

    private function setFormCurlInfos($inArray)
    {
        return '<div class="tabbertab" id="tabCurl" title="CURL infos">'
                . $this->displayTableFromMultiLevelArray([
                    'source'       => $this->localConfiguration['info'],
                    'destination'  => $this->serverConfiguration['info'],
                    'Servers'      => $this->config['Servers'],
                    'SuperGlobals' => $inArray['SuperGlobals'],
                ])
                . '</div><!--from tabCurl-->';
    }

    private function setFormInfos($inArray)
    {
        return '<div class="tabbertab'
                . (is_null($inArray['SuperGlobals']->get('Label')) ? '' : ' tabbertabdefault')
                . '" id="tabConfigs" title="Informations">'
                . $this->displayTableFromMultiLevelArray([
                    'source'       => $this->localConfiguration['response'],
                    'destination'  => $this->serverConfiguration['response'],
                    'Servers'      => $this->config['Servers'],
                    'SuperGlobals' => $inArray['SuperGlobals'],
                ])
                . '</div><!--from tabConfigs-->';
    }

    private function setHeaderHtml()
    {
        return $this->setHeaderCommon([
                    'lang'       => 'en-US',
                    'title'      => $this->configuredApplicationName(),
                    'css'        => 'css/main.css',
                    'javascript' => 'js/tabber.min.js',
                ])
                . $this->setJavascriptContent('document.write(\'<style type="text/css">'
                        . '.tabber{display:none;}</style>\');')
                . '<h1>' . $this->configuredApplicationName() . '</h1>'
                . '<div class="tabber" id="tab">';
    }
}
