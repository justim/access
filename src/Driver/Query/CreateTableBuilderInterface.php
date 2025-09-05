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

namespace Access\Driver\Query;

use Access\Schema\Field;
use Access\Schema\Index;

/**
 * @author Tim <me@justim.net>
 * @internal
 */
interface CreateTableBuilderInterface
{
    public function primaryKey(Field $field): string;
    public function foreignKey(Field $field): string;
    public function index(Index $index): string;
}
