<?php

namespace ShhStore\Policies;

use App\Policies\DefaultAdminPolicies;

class StoreCategoryPolicy
{
    use DefaultAdminPolicies;

    protected string $modelName = 'storeCategory';
}
