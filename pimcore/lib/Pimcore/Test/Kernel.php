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

namespace Pimcore\Test;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class Kernel extends \Pimcore\Kernel
{
    /**
     * @var BundleInterface[]
     */
    private $testBundles = [];

    /**
     * @param BundleInterface[] $testBundles
     */
    public function setTestBundles(array $testBundles)
    {
        $this->testBundles = $testBundles;
    }

    /**
     * @inheritDoc
     */
    public function registerBundles(): array
    {
        $bundles = parent::registerBundles();
        $bundles = array_merge($bundles, $this->testBundles);

        return $bundles;
    }
}
