<?php
declare(strict_types=1);
namespace YoastSeoForTypo3\YoastSeo\Service;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use YoastSeoForTypo3\YoastSeo\Utility\YoastUtility;

/**
 * Class UrlService
 */
class UrlService implements SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Backend\Routing\UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var \Psr\Http\Message\UriInterface|null
     */
    protected $generatedUri;

    /**
     * UrlService constructor.
     */
    public function __construct()
    {
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    }

    /**
     * Get target url
     *
     * @param int $pageId
     * @param int $languageId
     * @param string $additionalGetVars
     * @return string
     */
    public function getPreviewUrl(
        int $pageId,
        int $languageId,
        $additionalGetVars = ''
    ): string {
        $this->checkMountpoint($pageId, $additionalGetVars);
        $rootLine = $this->getRootLine($pageId);
        $site = $this->getSite($pageId, $rootLine);

        if ($site !== null) {
            $uriToCheck = YoastUtility::fixAbsoluteUrl(
                (string)$this->generateUri($site, $pageId, $languageId, $additionalGetVars)
            );

            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['urlToCheck'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['urlToCheck'] as $_funcRef) {
                    $_params = [
                        'urlToCheck' => $uriToCheck,
                        'site' => $site,
                        'finalPageIdToShow' => $pageId,
                        'languageId' => $languageId
                    ];

                    $uriToCheck = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                }
            }
            $uri = (string)$this->uriBuilder->buildUriFromRoute('ajax_yoast_preview', [
                'uriToCheck' => $uriToCheck, 'pageId' => $pageId
            ]);
        } else {
            $uri = BackendUtility::getPreviewUrl($pageId, '', $rootLine, '', '', $additionalGetVars);
        }

        return $uri;
    }

    /**
     * @param int $pageId
     * @param     $additionalGetVars
     */
    public function checkMountPoint(int &$pageId, &$additionalGetVars): void
    {
        $pageRepository = $this->getPageRepository();
        $mountPointInformation = $pageRepository->getMountPointInfo($pageId);
        if ($mountPointInformation && $mountPointInformation['overlay']) {
            // New page id
            $pageId = $mountPointInformation['mount_pid'];
            $additionalGetVars .= '&MP=' . $mountPointInformation['MPvar'];
        }
    }

    /**
     * @param int $pageId
     * @return array
     */
    public function getRootLine(int $pageId): array
    {
        return BackendUtility::BEgetRootLine($pageId);
    }

    /**
     * @param int   $pageId
     * @param array $rootLine
     * @return \TYPO3\CMS\Core\Site\Entity\Site|null
     */
    public function getSite(int $pageId, array $rootLine): ?Site
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        try {
            return $siteFinder->getSiteByPageId($pageId, $rootLine);
        } catch (SiteNotFoundException $e) {
            return null;
        }
    }

    /**
     * @param \TYPO3\CMS\Core\Site\Entity\Site $site
     * @param int                              $pageId
     * @param int                              $languageId
     * @param string                           $additionalGetVars
     * @return \Psr\Http\Message\UriInterface
     * @throws \TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException
     */
    public function generateUri(Site $site, int $pageId, int $languageId, $additionalGetVars = ''): UriInterface
    {
        $additionalQueryParams = [];
        parse_str($additionalGetVars, $additionalQueryParams);
        $additionalQueryParams['_language'] = $site->getLanguageById($languageId);
        return $this->generatedUri = $site->getRouter()->generateUri($pageId, $additionalQueryParams);
    }

    /**
     * Get save scores url
     *
     * @return string
     */
    public function getSaveScoresUrl(): string
    {
        try {
            return (string)$this->uriBuilder->buildUriFromRoute('ajax_yoast_save_scores');
        } catch (RouteNotFoundException $e) {
            return '';
        }
    }

    /**
     * @param int $type
     * @param string $additionalGetParameters
     * @return string
     */
    public function getUrlForType(int $type, $additionalGetParameters = ''): string
    {
        return GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . '?type=' . $type . $additionalGetParameters;
    }

    /**
     * @return \Psr\Http\Message\UriInterface|null
     */
    public function getGeneratedUri(): ?UriInterface
    {
        return $this->generatedUri;
    }

    /**
     * @return \TYPO3\CMS\Core\Domain\Repository\PageRepository|\TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected function getPageRepository()
    {
        if (class_exists(PageRepository::class)) {
            return GeneralUtility::makeInstance(PageRepository::class);
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
