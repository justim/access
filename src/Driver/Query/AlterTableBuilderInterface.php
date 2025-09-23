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

use Access\Clause\Field as ClauseField;
use Access\Schema\Field;
use Access\Schema\Index;
use Access\Schema\Table;

/**
 * @author Tim <me@justim.net>
 * @internal
 */
interface AlterTableBuilderInterface
{
    public function renameTable(Table|string $table): string;

    public function addField(Field $field): string;
    public function removeField(ClauseField $field): string;
    public function changeField(ClauseField $from, Field $to): string;
    public function modifyField(Field $to): string;
    public function renameField(ClauseField $from, ClauseField $to): string;

    public function addIndex(Index $index): string;
    public function removeIndex(Index|string $index): string;
    public function renameIndex(Index|string $from, Index|string $to): string;
}
