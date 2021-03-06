<?php

/*
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2017 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */

namespace Opulence\Tests\Ioc\Bootstrappers\Mocks;

/**
 * Defines a class that implement the foo interface
 */
class LazyConcreteFoo implements LazyFooInterface
{
    /**
     * @inheritdoc
     */
    public function getClass() : string
    {
        return self::class;
    }
}
