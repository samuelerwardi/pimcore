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

interface ITracker
{
    public function __construct(ITrackingItemBuilder $trackingItemBuilder, EngineInterface $templatingEngine);

    public function getTrackingItemBuilder(): ITrackingItemBuilder;

    public function includeDependencies();
}
