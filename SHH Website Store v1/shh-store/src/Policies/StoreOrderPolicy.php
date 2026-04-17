<?php

namespace ShhStore\Policies;

use App\Policies\DefaultAdminPolicies;

class StoreOrderPolicy
{
    use DefaultAdminPolicies;

    protected string $modelName = 'storeOrder';
}
