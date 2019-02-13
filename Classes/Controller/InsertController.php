<?php

namespace Sng\Recordsmanager\Controller;

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

class InsertController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    protected $currentConfig;

    /**
     * action index
     *
     * @return void
     */
    public function indexAction()
    {
        $allConfigs = \Sng\Recordsmanager\Utility\Config::getAllConfigs(1);

        $formResultCompiler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\FormResultCompiler::class);
        $formResultCompiler->printNeededJSFunctions();

        if (empty($allConfigs)) {
            return null;
        }

        $this->currentConfig = $allConfigs[0];
        $this->setCurrentConfig();
        $arguments = $this->request->getArguments();

        $temp_sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
        $addWhere = ' AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1) . ' ';

        $pids = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'count(' . $this->currentConfig['sqltable'] . '.pid) as "nbrecords",' . $this->currentConfig['sqltable'] . '.pid,pages.title',
            $this->currentConfig['sqltable'] . ',pages',
            'pages.uid=' . $this->currentConfig['sqltable'] . '.pid AND ' . $this->currentConfig['sqltable'] . '.deleted=0 ' . $addWhere . 'GROUP BY ' . $this->currentConfig['sqltable'] . '.pid '
        );

        $content = '';

        $pidsFind = array();
        $pidsAdmin = array();

        // All find PIDs
        if (count($pids) > 0) {
            foreach ($pids as $pid) {
                $rootline = $temp_sys_page->getRootLine($pid['pid']);
                $path = $temp_sys_page->getPathFromRootline($rootline, 30);
                $pidsFind[] = array('pid' => $pid['pid'], 'path' => $path, 'nbrecords' => $pid['nbrecords']);
            }
        }

        // Admin specified PIDs
        if ($this->currentConfig['insertdefaultpid'] != '') {
            $pids = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                'pages.uid,pages.title',
                'pages',
                'pages.deleted=0 AND pages.uid IN (' . $this->currentConfig['insertdefaultpid'] . ')' . $addWhere
            );
            if (count($pids) > 0) {
                foreach ($pids as $pid) {
                    $nb = count($GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $this->currentConfig['sqltable'] . '', 'pid=' . $pid['uid'] . ' AND deleted=0 '));
                    $rootline = $temp_sys_page->getRootLine($pid['uid']);
                    $path = $temp_sys_page->getPathFromRootline($rootline, 30);
                    $pidsAdmin[] = array('pid' => $pid['uid'], 'path' => $path, 'nbrecords' => $nb);
                }
            }
        }

        $this->view->assign('pidsfind', $pidsFind);
        $this->view->assign('pidsadmin', $pidsAdmin);
        $this->view->assign('currentconfig', $this->currentConfig);
        $this->view->assign('arguments', $arguments);
        $this->view->assign('menuitems', $allConfigs);
        $this->view->assign('returnurl', $this->getReturnUrl());
        $this->view->assign('browserurl', $this->getBrowserUrl());

        // redirect to tce form
        if (!empty($arguments['create'])) {
            $this->redirectToForm($arguments['create']);
        }

    }

    /**
     * Get return url
     *
     * @return string
     */
    public function getReturnUrl()
    {
        $arguments = $this->request->getArguments();
        return $this->uriBuilder->reset()->setAddQueryString(true)->uriFor();
    }

    /**
     * Set the current config record
     */
    public function setCurrentConfig()
    {
        $arguments = $this->request->getArguments();
        if (!empty($arguments['menuitem'])) {
            $this->currentConfig = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tx_recordsmanager_config', 'uid=' . intval($arguments['menuitem']));
        }
    }

    /**
     * Get url to browser pages
     *
     * @return string
     */
    public function getBrowserUrl()
    {
        $browserUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('wizard_element_browser');
        return $browserUrl;
    }

    /**
     * Redirect to the insert form with correct params
     *
     * @param int $id
     */
    public function redirectToForm($id)
    {
        $arguments = $this->request->getArguments();
        $returnUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('txrecordsmanagerM1_RecordsmanagerInsert');
        if (!empty($arguments['menuitem'])) {
            $returnUrl .= '&tx_recordsmanager_txrecordsmanagerm1_recordsmanagerinsert[menuitem]=' . $arguments['menuitem'];
        }
        if (version_compare(TYPO3_version, '7.0.0', '>=')) {
            $editLink = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('record_edit') . '&returnUrl=' . rawurlencode($returnUrl) . '&edit[' . $this->currentConfig['sqltable'] . '][' . $id . ']=new';
        } else {
            $editLink = 'alt_doc.php?returnUrl=' . rawurlencode($returnUrl) . '&edit[' . $this->currentConfig['sqltable'] . '][' . $id . ']=new';
        }
        // disabledFields
        $this->disableFields = implode(',', \tx_recordsmanager_flexfill::getDiffFieldsFromTable($this->currentConfig['sqltable'], $this->currentConfig['sqlfieldsinsert']));
        if ($this->currentConfig['sqlfieldsinsert'] !== '') {
            $editLink .= '&recordsHide=' . $this->disableFields;
        }
        \TYPO3\CMS\Core\Utility\HttpUtility::redirect($editLink);
    }

}
