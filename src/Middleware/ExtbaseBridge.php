<?php
declare(strict_types=1);
namespace B13\SlimPhp\Middleware;

/*
 * This file is part of TYPO3 CMS-based extension "SlimPHP Bridge" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Sets up TSFE and Extbase, in order to use Extbase within a Slim Controller
 */
class ExtbaseBridge implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $handler->handle($request);
        }
        if (!$GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            $this->createGlobalTsfe($site, $request);
        } else {
            $GLOBALS['TSFE']->id = $site->getRootPageId();
        }
        $this->bootFrontend($request);
        $this->bootExtbase();
        return $handler->handle($request);
    }

    protected function createGlobalTsfe(Site $site, ServerRequestInterface $request): void
    {
        if (version_compare(TYPO3_version, '10.4', '>=')) {
            $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                null,
                $site,
                $request->getAttribute('language'),
                null,
                $request->getAttribute('frontend.user', null)
            );
        } else {
            $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                null,
                $site->getRootPageId(),
                0
            );
            $GLOBALS['TSFE']->initFEuser();
        }
    }

    protected function bootFrontend(ServerRequestInterface $request): void
    {
        if (version_compare(TYPO3_version, '10.4', '>=')) {
            $GLOBALS['TSFE']->fetch_the_id($request);
            $GLOBALS['TSFE']->getConfigArray($request);
            $GLOBALS['TSFE']->settingLanguage($request);
            $GLOBALS['TSFE']->newCObj();
        } else {
            $GLOBALS['TSFE']->fetch_the_id();
            $GLOBALS['TSFE']->getConfigArray();
            $GLOBALS['TSFE']->settingLanguage();
            $GLOBALS['TSFE']->settingLocale();
            $GLOBALS['TSFE']->newCObj();
        }
    }

    protected function bootExtbase(): void
    {
        GeneralUtility::makeInstance(Bootstrap::class)->initialize([
            'extensionName' => 'slimphp',
            'vendorName' => 'B13',
            'pluginName' => 'slimphp'
        ]);
    }
}
