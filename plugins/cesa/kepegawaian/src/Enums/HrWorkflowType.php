<?php

namespace Cesa\Kepegawaian\Enums;

enum HrWorkflowType: string
{
    case Onboarding = 'onboarding';
    case Offboarding = 'offboarding';
    case ContractRenewal = 'contract_renewal';
    case Disciplinary = 'disciplinary';
    case Training = 'training';
    case Offering = 'offering';
    case Custom = 'custom';
}
