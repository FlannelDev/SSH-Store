<?php

namespace ShhStore\Policies;

use App\Policies\DefaultAdminPolicies;

class StoreProductPolicy
{
    use DefaultAdminPolicies;

    protected string $modelName = 'storeProduct';
}
