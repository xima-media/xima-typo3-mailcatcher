<?php

namespace Xima\XimaTypo3Mailcatcher\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Xima\XimaTypo3Mailcatcher\Utility\LogParserUtility;

class BackendController extends ActionController
{
    private ModuleTemplateFactory $moduleTemplateFactory;

    private PageRenderer $pageRenderer;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        PageRenderer $pageRenderer
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->pageRenderer = $pageRenderer;
    }

    public function indexAction(): ResponseInterface
    {
        $parser = GeneralUtility::makeInstance(LogParserUtility::class);
        $parser->run();
        $mails = $parser->loadAndGetMessages();
        $this->view->assign('mails', $mails);

        $version = VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getNumericTypo3Version());
        if ($version['version_main'] >= 12) {
            $this->pageRenderer->loadJavaScriptModule('@xima/xima-typo3-mailcatcher/MailCatcher.js');
        } else {
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/XimaTypo3Mailcatcher/MailCatcher');
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }
}
