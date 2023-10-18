<?php

namespace Restock\Enum;

enum GroupMemberRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';

    public static function all(): array
    {
        return self::cases();
    }
}
