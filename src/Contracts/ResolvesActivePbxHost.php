<?php

namespace CXEngine\ExpertStatistics\Contracts;

/**
 * Resolves the PBX 3CX host name that Dashboard/Expert Statistics data should
 * be fetched for in the current request. bluerocktelclients keys this off
 * `session('pbx_hostname')`; other host apps (e.g. bluerocktel-cx) resolve it
 * from their own tenant/customer scoping (e.g. the active Pbx3cxHost record).
 * The package never assumes either — the host app binds this contract.
 */
interface ResolvesActivePbxHost
{
    public function getActiveHostName(): ?string;
}
