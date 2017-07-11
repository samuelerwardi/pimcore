<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

abstract class Tracker implements ITracker
{
    /**
     * @var ITrackingItemBuilder
     */
    protected $trackingItemBuilder;

    /**
     * @var EngineInterface
     */
    protected $templatingEngine;

    /**
     * @var string
     */
    protected $templateExtension = 'php';

    public function __construct(
        ITrackingItemBuilder $trackingItemBuilder,
        EngineInterface $templatingEngine,
        string $templateExtension = 'php'
    )
    {
        $this->trackingItemBuilder = $trackingItemBuilder;
        $this->templatingEngine    = $templatingEngine;
        $this->templateExtension   = $templateExtension;
    }

    public function setTemplateSuffix(string $suffix)
    {
        $this->templateExtension = $suffix;
    }

    abstract protected function getTemplatePrefix(): string;

    protected function getTemplatePath(string $name)
    {
        return sprintf(
            'PimcoreEcommerceFrameworkBundle:Tracking/%s:%s.js.%s',
            $this->getTemplatePrefix(),
            $name,
            $this->templateExtension
        );
    }

    protected function renderTemplate(string $name, array $parameters): string
    {
        return $this->templatingEngine->render(
            $this->getTemplatePath($name),
            $parameters
        );
    }

    /**
     * Remove null values from an object, keep protected keys in any case
     *
     * @param array $data
     * @param array $protectedKeys
     *
     * @return array
     */
    protected function filterNullValues(array $data, array $protectedKeys = []): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $isProtected = in_array($key, $protectedKeys);
            if (null !== $value || $isProtected) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
