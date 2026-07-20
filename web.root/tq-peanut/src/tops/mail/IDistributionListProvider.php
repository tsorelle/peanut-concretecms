<?php

namespace Tops\mail;

interface IDistributionListProvider
{
    function GetDistributionEmails(string $distributionCode);
}