<?php

declare(strict_types=1);

namespace Foundation\Iam\Shadows;

use Modulith\Models\ShadowModel;

/**
 * @property int $id
 * @property string $name
 */
abstract class UserShadow extends ShadowModel
{
    public static function owner(): string
    {
        return 'iam';
    }

    public static function sourceTable(): string
    {
        return 'iam_users';
    }
}
