<?php

use CXEngine\ExpertStatistics\Support\RouteDiagram;

function diagramRoute(): array
{
    return ['nodes' => [
        ['id' => 'inbound_rule:0100', 'type' => 'inbound_rule', 'label' => 'Règle entrante / SDA 0100', 'next' => [['condition' => 'all', 'to' => 'queue:809']]],
        ['id' => 'queue:809', 'type' => 'queue', 'label' => 'File 809 – "Support"', 'details' => ['members' => [['dn' => '1'], ['dn' => '2']], 'members_logged_in' => 1, 'max_wait_seconds' => 300], 'next' => [
            ['condition' => 'after max wait 300 s', 'to' => 'ivr:805'],
            ['condition' => 'out of office hours', 'to' => 'call_flow_app:899'],
            ['condition' => 'holiday', 'to' => 'call_flow_app:899'],
        ]],
        ['id' => 'ivr:805', 'type' => 'ivr', 'label' => 'SVI 805 – Débordements', 'details' => ['keys' => 'none (announcement)', 'timeout_seconds' => 2], 'next' => [['condition' => 'after the announcement (2 s)', 'to' => 'end_of_call:announcement']]],
        ['id' => 'call_flow_app:899', 'type' => 'call_flow_app', 'label' => 'Call Flow App supportv2', 'details' => ['external_calls' => ['extranet.example']], 'next' => [
            ['condition' => 'condition Verif_Speciaux_0: (DateTime.Now.Year == 2023 && DateTime.Now.Month == 6)', 'to' => 'loop:queue:809'],
            ['condition' => 'condition Verif_Speciaux_1: (DateTime.Now.Hour > 0) › condition BR_OK: OR(NOT_EQUAL(a,500)) › found = "True" › vip = "True"', 'to' => 'loop:queue:809'],
        ]],
        ['id' => 'loop:queue:809', 'type' => 'loop', 'label' => 'Boucle de routage détectée (File 809)', 'next' => []],
        ['id' => 'end_of_call:announcement', 'type' => 'end_of_call', 'label' => "Annonce puis fin d'appel", 'next' => []],
    ]];
}

it('draws one box per object, coloured by its most severe recommendation', function () {
    $diagram = RouteDiagram::mermaid(diagramRoute(), [
        ['object' => 'ivr:805', 'category' => 'optimisation'],
        ['object' => 'ivr:805', 'category' => 'to_check'],
        ['object' => 'queue:809', 'category' => 'probable_error'],
    ]);

    expect($diagram)->toStartWith('flowchart TD')
        ->toContain('n1["File 809 – \'Support\'<br/><small>2 agents (1 ✓) · max 300 s</small><br/><b>⚠ 1</b>"]')
        ->toContain('<br/><b>⚠ 2</b>"}}')
        ->toContain("n4([\"Annonce puis fin d'appel\"])")
        ->toContain('class n1 probable_error')
        ->toContain('class n2 to_check')
        ->toContain('class n4 terminal')
        // Every coloured style sets its text colour, for the dark theme.
        ->toMatch('/classDef to_check fill:#\w+,stroke:#\w+,color:#\w+/');
});

it('colours office hours arrows and Call Flow App branches', function () {
    $diagram = RouteDiagram::mermaid(diagramRoute());

    expect($diagram)->toContain('linkStyle 2 stroke:'.RouteDiagram::ARROWS['hours'])
        ->toContain('linkStyle 4 stroke:'.RouteDiagram::ARROWS['call_flow']);
});

it('merges the conditions of one arrow and draws loops back to the object', function () {
    $diagram = RouteDiagram::mermaid(diagramRoute());

    // The loop marker is not drawn: the Call Flow goes back to the queue, named by its conditions.
    expect($diagram)->toContain('n1 -->|"out of office hours / holiday"| n3')
        ->not->toContain('Boucle de routage')
        ->toContain('n3 -.->|"Verif_Speciaux_0 / … › found = \'True\' › vip = \'True\'"| n1')
        ->not->toContain('DateTime');
});

it('draws nothing for an empty route', function () {
    expect(RouteDiagram::mermaid(['nodes' => []]))->toBeNull();
});
