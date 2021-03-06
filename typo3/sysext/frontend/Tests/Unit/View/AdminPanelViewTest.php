<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\View;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case
 */
class AdminPanelViewTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Set up
     */
    protected function setUp()
    {
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['TSFE'] = new TypoScriptFrontendController([], 1, 1);
    }

    /**
     * @test
     */
    public function extGetFeAdminValueReturnsTimestamp()
    {
        $strTime = '2013-01-01 01:00:00';
        $timestamp = strtotime($strTime);

        $backendUser = $this->getMockBuilder(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class)->getMock();
        $backendUser->uc['TSFE_adminConfig']['preview_simulateDate'] = $timestamp;
        unset($backendUser->extAdminConfig['override.']['preview.']);
        unset($backendUser->extAdminConfig['override.']['preview']);
        $GLOBALS['BE_USER'] = $backendUser;

        $adminPanelMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\View\AdminPanelView::class)
            ->setMethods(['isAdminModuleEnabled', 'isAdminModuleOpen'])
            ->disableOriginalConstructor()
            ->getMock();
        $adminPanelMock->expects($this->any())->method('isAdminModuleEnabled')->will($this->returnValue(true));
        $adminPanelMock->expects($this->any())->method('isAdminModuleOpen')->will($this->returnValue(true));

        $timestampReturned = $adminPanelMock->extGetFeAdminValue('preview', 'simulateDate');
        $this->assertEquals($timestamp, $timestampReturned);
    }

    /////////////////////////////////////////////
    // Test concerning extendAdminPanel hook
    /////////////////////////////////////////////

    /**
     * @test
     */
    public function extendAdminPanelHookThrowsExceptionIfHookClassDoesNotImplementInterface()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1311942539);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'][] = \TYPO3\CMS\Frontend\Tests\Unit\Fixtures\AdminPanelHookWithoutInterfaceFixture::class;
        /** @var $adminPanelMock \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\View\AdminPanelView */
        $adminPanelMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\View\AdminPanelView::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $adminPanelMock->display();
    }

    /**
     * @test
     */
    public function extendAdminPanelHookCallsExtendAdminPanelMethodOfHook()
    {
        $hookClass = $this->getUniqueId('tx_coretest');
        $hookMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\View\AdminPanelViewHookInterface::class)
            ->setMockClassName($hookClass)
            ->getMock();
        GeneralUtility::addInstance($hookClass, $hookMock);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'][] = $hookClass;
        /** @var $adminPanelMock \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\View\AdminPanelView */
        $adminPanelMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\View\AdminPanelView::class)
            ->setMethods(['extGetLL'])
            ->disableOriginalConstructor()
            ->getMock();
        $adminPanelMock->initialize();
        $hookMock->expects($this->once())->method('extendAdminPanel')->with($this->isType('string'), $this->isInstanceOf(\TYPO3\CMS\Frontend\View\AdminPanelView::class));
        $adminPanelMock->display();
    }
}
