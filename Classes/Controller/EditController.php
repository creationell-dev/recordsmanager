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

class EditController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    protected $currentConfig;

    /**
     * action index
     *
     * @return void
     */
    public function indexAction()
    {
        $allConfigs = \Sng\Recordsmanager\Utility\Config::getAllConfigs(0);

        if (empty($allConfigs)) {
            return null;
        }

        $this->currentConfig = $allConfigs[0];
        $this->setCurrentConfig();

        $query = $this->buildQuery();
        $query->execQuery();

        $this->view->assign('headers', $query->getHeaders());
        $this->view->assign('rows', $query->getRows());
        $this->view->assign('currentconfig', $this->currentConfig);
        $this->view->assign('arguments', $this->request->getArguments());
        $this->view->assign('menuitems', $allConfigs);
        $this->view->assign('returnurl', rawurlencode($this->getReturnUrl()));
        $this->view->assign('deleteurl', $this->getDeleteUrl());
        $this->view->assign('baseediturl', $this->getBaseEditUrl());
        $this->view->assign('disableFields', implode(',', \tx_recordsmanager_flexfill::getDiffFieldsFromTable($this->currentConfig['sqltable'], $this->currentConfig['sqlfieldsinsert'])));
    }

    /**
     * Build the query array
     *
     * @return Tx_Recordsmanager_Utility_Query
     */
    public function buildQuery()
    {
        $arguments = $this->request->getArguments();

        $queryObject = new  \Sng\Recordsmanager\Utility\Query();
        $queryObject->setConfig($this->currentConfig);
        $queryObject->buildQuery();

        if (!empty($arguments['orderby'])) {
            $queryObject->setOrderBy(rawurldecode($arguments['orderby']));
        }

        return $queryObject;
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
     * Get url to delete a record
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        $arguments = $this->request->getArguments();
        $returnUrl = $this->getReturnUrl();
        $deleteUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('tce_db');
        $deleteUrl .= '&cmd["+table+"]["+id+"][delete]=1&redirect=' . rawurlencode($returnUrl) . '&vC=' . $GLOBALS['BE_USER']->veriCode() . '&prErr=1&uPT=1';
        return $deleteUrl;
    }

    /**
     * Get url to edit a record
     *
     * @return string
     */
    public function getBaseEditUrl()
    {
        if (version_compare(TYPO3_version, '7.0.0', '>=')) {
            return \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('record_edit') . '&';
        } else {
            return 'alt_doc.php?';
        }
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

}

