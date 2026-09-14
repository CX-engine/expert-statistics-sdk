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

    /**
     * Every host the current context could switch to, for a simple picker
     * UI (ManagePbxSettings' Active Host tab). Returns [] when there's no
     * way to resolve a list (e.g. no customer selected in this request).
     *
     * @return array<int, array{name: string, label: string, active: bool}>
     */
    public function getAvailableHosts(): array;

    /**
     * Makes getActiveHostName() return $hostName going forward, for
     * whatever counts as "current context" host-app-side (e.g. the
     * customer already resolved elsewhere in the request). Silently a
     * no-op if $hostName isn't one of getAvailableHosts().
     */
    public function setActiveHost(string $hostName): void;
}
