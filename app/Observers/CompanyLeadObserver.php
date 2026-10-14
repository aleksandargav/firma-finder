<?php

namespace App\Observers;

use App\Models\CompanyLead;

class CompanyLeadObserver
{
    public function saving(CompanyLead $companyLead): void
    {
        if ($companyLead->isDirty('website_status')) {
            $companyLead->website_checked_at = now();
        }

        if ($companyLead->lead_status === 'contacted' && ! $companyLead->contacted_at) {
            $companyLead->contacted_at = now();
        }
    }
}
