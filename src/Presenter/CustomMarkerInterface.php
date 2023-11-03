<?php

/*
 * This file is part of the Access package.
 *
 * (c) Tim <me@justim.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Access\Presenter;

/**
 * Custom marker
 *
 * Inject your own markers to lazily resolve at a later time
 *
 * @author Tim <me@justim.net>
 */
interface CustomMarkerInterface
{
    /**
     * Fetch information from the marker
     */
    public function fetch(): mixed;
}
