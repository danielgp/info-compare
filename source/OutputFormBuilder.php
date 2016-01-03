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

trait OutputFormBuilder
{

    private function listOfKnownLabels($inArray)
    {
        $informatorKnownLabels = array_diff($inArray['KnownLabels'], ['--- List of known labels']);
        $tmpOptions            = [];
        foreach ($informatorKnownLabels as $value) {
            $tmpOptions[] = '<input type="radio" name="Label" id="Label_' . $value . '" value="' . $value . '" '
                    . $this->turnRequestedValueIntoCheckboxStatus([
                        'SuperGlobals'  => $inArray['SuperGlobals'],
                        'RequestedName' => 'Label',
                        'CheckedValue'  => $value,
                    ])
                    . '/>'
                    . '<label for="Label_' . $value . '">' . $value . '</label>';
        }
        return '<fieldset style="float:left;">'
                . '<legend>Informator Label to use</legend>'
                . implode('<br/>', $tmpOptions)
                . '</fieldset>';
    }

    private function providers($inArray)
    {
        $tmpOptions = [];
        foreach ($inArray['Servers'] as $key => $value) {
            $tmpOptions[] = '<a href="' . $value['url'] . '" target="_blank">run-me</a>&nbsp;'
                    . '<input type="radio" name="' . $inArray['ConfigName'] . '" id="'
                    . $inArray['ConfigName'] . '_' . $key . '" value="' . $key . '" '
                    . $this->turnRequestedValueIntoCheckboxStatus([
                        'SuperGlobals'  => $inArray['SuperGlobals'],
                        'RequestedName' => $inArray['ConfigName'],
                        'CheckedValue'  => (string) $key,
                    ])
                    . '/>'
                    . '<label for="' . $inArray['ConfigName'] . '_' . $key . '">' . $value['name'] . '</label>';
        }
        return '<fieldset style="float:left;">'
                . '<legend>' . $inArray['TitleStart'] . ' config providers</legend>'
                . implode('<br/>', $tmpOptions)
                . '</fieldset>';
    }

    protected function setFormOptions($inArray)
    {
        $sReturn   = [];
        $sReturn[] = $this->typeOfResults($inArray['SuperGlobals']);
        $sReturn[] = $this->providers([
            'ConfigName'   => 'localConfig',
            'Servers'      => $inArray['Servers'],
            'SuperGlobals' => $inArray['SuperGlobals'],
            'TitleStart'   => 'Source',
        ]);
        $sReturn[] = $this->providers([
            'ConfigName'   => 'serverConfig',
            'Servers'      => $inArray['Servers'],
            'SuperGlobals' => $inArray['SuperGlobals'],
            'TitleStart'   => 'Target',
        ]);
        $sReturn[] = $this->listOfKnownLabels($inArray);
        return '<div class="tabbertab" id="tabOptions" title="Options">'
                . '<style type="text/css" media="all" scoped>label { width: auto; }</style>'
                . '<form method="get" action="'
                . $inArray['SuperGlobals']->server->get('PHP_SELF')
                . '">'
                . '<input type="submit" value="Apply" />'
                . '<br/>' . implode('', $sReturn)
                . '</form>'
                . '<div style="float:none;clear:both;height:1px;">&nbsp;</div>'
                . '</div><!--from tabOptions-->';
    }

    private function turnRequestedValueIntoCheckboxStatus($inArray)
    {
        $checkboxStatus = '';
        if ($inArray['SuperGlobals']->get($inArray['RequestedName']) === $inArray['CheckedValue']) {
            $checkboxStatus = 'checked ';
        }
        return $checkboxStatus;
    }

    private function typeOfResults($superGlobals)
    {
        return '<fieldset style="float:left;">'
                . '<legend>Type of results displayed</legend>'
                . '<input type="radio" name="displayOnlyDifferent" id="displayOnlyDifferent" value="1" '
                . $this->turnRequestedValueIntoCheckboxStatus([
                    'SuperGlobals'  => $superGlobals,
                    'RequestedName' => 'displayOnlyDifferent',
                    'CheckedValue'  => '1',
                ])
                . '/>'
                . '<label for="displayOnlyDifferent">Only the Different values</label>'
                . '<br/>'
                . '<input type="radio" name="displayOnlyDifferent" id="displayAll" value="0" '
                . $this->turnRequestedValueIntoCheckboxStatus([
                    'SuperGlobals'  => $superGlobals,
                    'RequestedName' => 'displayOnlyDifferent',
                    'CheckedValue'  => '0',
                ])
                . '/>'
                . '<label for="displayAll">All</label>'
                . '</fieldset>';
    }
}
