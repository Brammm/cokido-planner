<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

enum MemberRole: string
{
    case Admin = 'admin';
    case Planning = 'planning';
    case Financial = 'financial';
    case Parent = 'parent';
}
