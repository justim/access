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

namespace Tests\Fixtures\Presenter;

use Access\Entity;
use Access\Presenter\EntityPresenter;

/**
 * Broken without class presenter
 */
class BrokenWithoutEntityKlassPresenter extends EntityPresenter
{
    /**
     * SAFETY For testing
     * @psalm-suppress MoreSpecificReturnType
     */
    public static function getEntityKlass(): string
    {
        /**
         * SAFETY For testing
         * @psalm-suppress UndefinedClass
         * @psalm-suppress LessSpecificReturnStatement
         */
        return '';
    }

    public function fromEntity(Entity $entity): ?array
    {
        return null;
    }
}
