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

namespace Access\Query\Cursor;

use Access\Clause\Field;

/**
 * Cursor that uses an offset for a specific field
 *
 * Make sure the order of the field is consistent between queries
 *
 * @author Tim <me@justim.net>
 *
 * @psalm-suppress PropertyNotSetInConstructor Seems like a false positive, the parent constructor is called
 */
abstract class OffsetCursor extends Cursor
{
    /**
     * The field used for offset
     */
    private Field $field;

    /**
     * The current offset
     *
     * @var mixed $page
     */
    private mixed $offset = null;

    /**
     * Create a page cursor
     *
     * @param mixed $offset Current offset
     * @param int $pageSize Page size, defaults to 50
     */
    public function __construct(
        mixed $offset = null,
        Field|string|null $field = null,
        ?int $pageSize = self::DEFAULT_PAGE_SIZE,
    ) {
        parent::__construct($pageSize);

        $this->offset = $offset;

        if ($field instanceof Field) {
            $this->field = $field;
        } elseif (is_string($field)) {
            $this->field = new Field($field);
        } else {
            $this->field = new Field('id');
        }
    }

    /**
     * Get the field to use for the offset
     *
     * @return Field Field
     */
    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * Get the current offset
     *
     * @return mixed Offset
     */
    public function getOffset(): mixed
    {
        return $this->offset;
    }

    /**
     * Set the current offset
     *
     * @param mixed $offset Current offset
     */
    public function setOffset(mixed $offset): void
    {
        $this->offset = $offset;
    }
}
