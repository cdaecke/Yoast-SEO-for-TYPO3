<?php
declare(strict_types=1);
namespace YoastSeoForTypo3\YoastSeo\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class RecordLinksViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'uid of record to be edited', true);
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('command', 'string', '', true, '');
        $this->registerArgument('module', 'string', '', true, '');
    }

    /**
     * @param array                                                      $arguments
     * @param \Closure                                                   $renderChildrenClosure
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $returnUri = $uriBuilder->buildUriFromRoute($arguments['module'], GeneralUtility::_GET());

        $module = '';
        $urlParameters = [];
        switch ($arguments['command']) {
            case 'edit':
                $urlParameters = [
                    'edit' => [
                        $arguments['table'] => [
                            $arguments['uid'] => $arguments['command']
                        ]
                    ],
                    'returnUrl' => (string)$returnUri
                ];
                $module = 'record_edit';
                break;
            case 'delete':
                $urlParameters = [
                    'cmd[' . $arguments['table'] . '][' . $arguments['uid'] . '][delete]' => 1,
                    'redirect' => (string)$returnUri,
                ];
                $module = 'tce_db';
                break;
        }

        return (string)$uriBuilder->buildUriFromRoute($module, $urlParameters);
    }
}
