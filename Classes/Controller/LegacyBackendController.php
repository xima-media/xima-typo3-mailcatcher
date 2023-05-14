<?php

namespace Xima\XimaTypo3Mailcatcher\Controller;

use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Xima\XimaTypo3Mailcatcher\Utility\LogParserUtility;

class LegacyBackendController extends ActionController
{
    /**
     * @var BackendTemplateView
     */
    protected $view;

    protected $defaultViewObjectName = BackendTemplateView::class;

    public function indexAction(): void
    {
        $parser = GeneralUtility::makeInstance(LogParserUtility::class);
        $parser->run();
        $mails = $parser->getMessages();
        $this->view->assign('mails', $mails);

        $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/XimaTypo3Mailcatcher/MailCatcher');
    }
}
