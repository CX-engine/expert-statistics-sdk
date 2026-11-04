<?php

namespace CXEngine\ExpertStatistics\Support;

/**
 * Call flow diagram of one DID (Mermaid flowchart) from the resolved route
 * of the AI Audit: one box per routing object (its shape gives its type),
 * one arrow per pair of objects with its conditions (office hours, no answer
 * after N s, IVR key, Call Flow App branch with its API conditions).
 *
 * Colour is kept for what the reader must spot: objects with recommendations
 * are filled with the colour of their most severe one (with a "⚠ N" badge),
 * office-hours arrows are amber and Call Flow App branches indigo. Every
 * coloured style sets its text colour too, so it reads in light and dark
 * themes; the other boxes and arrows follow the Mermaid theme of the page.
 *
 * Labels come from the PBX configuration: they are reduced to plain text
 * (Mermaid also renders with securityLevel "strict").
 */
class RouteDiagram
{
    public const MAX_NODES = 120;

    /** Box styles, also used by the legend of the page. */
    public const STYLES = [
        'probable_error' => ['fill' => '#fee2e2', 'stroke' => '#dc2626', 'text' => '#7f1d1d'],
        'to_check' => ['fill' => '#fef3c7', 'stroke' => '#d97706', 'text' => '#78350f'],
        'optimisation' => ['fill' => '#e0f2fe', 'stroke' => '#0284c7', 'text' => '#0c4a6e'],
        'terminal' => ['fill' => '#4b5563', 'stroke' => '#374151', 'text' => '#f9fafb'],
        'unresolved' => ['fill' => '#fee2e2', 'stroke' => '#dc2626', 'text' => '#7f1d1d'],
    ];

    /** Arrow colours, also used by the legend of the page. */
    public const ARROWS = [
        'hours' => '#d97706',
        'call_flow' => '#6366f1',
    ];

    private const SEVERITY = ['probable_error' => 3, 'to_check' => 2, 'optimisation' => 1];

    private const SHAPES = [
        'inbound_rule' => ['([', '])'],
        'queue' => ['[', ']'],
        'ring_group' => ['[', ']'],
        'extension' => ['(', ')'],
        'ivr' => ['{{', '}}'],
        'call_flow_app' => ['{{', '}}'],
        'voicemail' => ['[/', '/]'],
        'external' => ['[/', '/]'],
        'end_of_call' => ['([', '])'],
        'loop' => ['>', ']'],
    ];

    /**
     * @param  array<string, mixed>  $route  route of the config-audit routes/{did} endpoint
     * @param  array<int, array<string, mixed>>  $findings  findings of the route
     */
    public static function mermaid(array $route, array $findings = []): ?string
    {
        $nodes = array_slice($route['nodes'] ?? [], 0, self::MAX_NODES);
        if ($nodes === []) {
            return null;
        }

        $target = self::target($nodes);
        $ids = array_flip(self::nodeIds($route));
        $types = array_column($nodes, 'type', 'id');

        // Most severe category and number of recommendations per object.
        $issues = [];
        foreach ($findings as $finding) {
            $object = $finding['object'] ?? null;
            $category = $finding['category'] ?? 'optimisation';
            if ($object === null) {
                continue;
            }
            $current = $issues[$object]['category'] ?? null;
            $issues[$object] = [
                'category' => $current === null || (self::SEVERITY[$category] ?? 0) > (self::SEVERITY[$current] ?? 0) ? $category : $current,
                'count' => ($issues[$object]['count'] ?? 0) + 1,
            ];
        }

        $lines = ['flowchart TD'];
        $classes = [];

        foreach ($nodes as $node) {
            if (! isset($ids[$node['id']])) {
                continue;
            }
            $type = $types[$node['id']] ?? 'unresolved';
            [$open, $close] = self::SHAPES[$type] ?? ['[', ']'];
            // Labels already name the object type ("File 809 – Support").
            $label = self::text($node['label'] ?? $node['id']);
            $sub = self::summary($type, $node['details'] ?? []);
            $issue = $issues[$node['id']] ?? null;
            $badge = $issue ? '<br/><b>⚠ '.$issue['count'].'</b>' : '';
            $lines[] = "    {$ids[$node['id']]}{$open}\"{$label}".($sub !== '' ? "<br/><small>{$sub}</small>" : '')."{$badge}\"{$close}";

            $class = match (true) {
                $issue !== null => $issue['category'],
                $type === 'end_of_call' => 'terminal',
                in_array($type, ['unresolved', 'loop'], true) => 'unresolved',
                default => null,
            };
            if ($class !== null && isset(self::STYLES[$class])) {
                $classes[$class][] = $ids[$node['id']];
            }
        }

        // One arrow per pair of objects, carrying its distinct conditions.
        $arrows = [];
        foreach ($nodes as $node) {
            if (! isset($ids[$node['id']])) {
                continue;
            }
            foreach ($node['next'] ?? [] as $next) {
                $to = $target((string) $next['to']);
                if (! isset($ids[$to])) {
                    continue;
                }
                $key = "{$node['id']}>{$to}";
                $arrows[$key] ??= ['from' => $ids[$node['id']], 'to' => $ids[$to], 'source' => $types[$node['id']] ?? 'unresolved', 'conditions' => [], 'raw' => '', 'dotted' => false];
                $condition = self::condition((string) ($next['condition'] ?? ''));
                if ($condition !== '' && ! in_array($condition, $arrows[$key]['conditions'], true)) {
                    $arrows[$key]['conditions'][] = $condition;
                }
                $arrows[$key]['raw'] .= ' '.($next['condition'] ?? '');
                $arrows[$key]['dotted'] = $arrows[$key]['dotted'] || ! empty($next['via_own_did']) || $to !== $next['to'];
            }
        }

        $styled = ['hours' => [], 'call_flow' => []];
        foreach (array_values($arrows) as $i => $arrow) {
            $conditions = $arrow['conditions'];
            $label = implode(' / ', array_slice($conditions, 0, 2)).(count($conditions) > 2 ? ' (+'.(count($conditions) - 2).')' : '');
            $lines[] = "    {$arrow['from']} ".($arrow['dotted'] ? '-.->' : '-->').($label !== '' ? "|\"{$label}\"|" : '')." {$arrow['to']}";
            if ($arrow['source'] === 'call_flow_app') {
                $styled['call_flow'][] = $i;
            } elseif (preg_match('/hours|holiday|break|horaire|férié|pause/i', $arrow['raw'])) {
                $styled['hours'][] = $i;
            }
        }

        foreach (self::STYLES as $class => $style) {
            $dash = $class === 'unresolved' ? ',stroke-dasharray:5 4' : '';
            $width = isset(self::SEVERITY[$class]) ? 3 : 2;
            $lines[] = "    classDef {$class} fill:{$style['fill']},stroke:{$style['stroke']},color:{$style['text']},stroke-width:{$width}px{$dash}";
        }
        foreach ($classes as $class => $members) {
            $lines[] = '    class '.implode(',', $members)." {$class}";
        }
        if ($arrows !== []) {
            $lines[] = '    linkStyle default stroke-width:1.5px';
        }
        foreach ($styled as $kind => $indexes) {
            if ($indexes !== []) {
                $lines[] = '    linkStyle '.implode(',', $indexes).' stroke:'.self::ARROWS[$kind].',stroke-width:2.5px';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Mermaid id of each drawn box => routing object id ("n1" => "queue:809"),
     * so the page can attach the recommendations to the boxes.
     *
     * @return array<string, string>
     */
    public static function nodeIds(array $route): array
    {
        $nodes = array_slice($route['nodes'] ?? [], 0, self::MAX_NODES);
        $target = self::target($nodes);

        $ids = [];
        foreach ($nodes as $node) {
            if ($target($node['id']) === $node['id']) {
                $ids['n'.count($ids)] = $node['id'];
            }
        }

        return $ids;
    }

    /**
     * A route marks an object met again as "loop:<id>": the diagram draws the
     * arrow back to that object instead of a separate box.
     */
    private static function target(array $nodes): \Closure
    {
        $present = array_flip(array_column($nodes, 'id'));

        return fn (string $id) => str_starts_with($id, 'loop:') && isset($present[substr($id, 5)]) ? substr($id, 5) : $id;
    }

    /**
     * One line under the label: what matters to read the route.
     */
    private static function summary(string $type, array $details): string
    {
        $parts = match ($type) {
            'queue' => [
                isset($details['members']) ? count($details['members']).' agents ('.($details['members_logged_in'] ?? 0).' ✓)' : null,
                isset($details['max_wait_seconds']) ? "max {$details['max_wait_seconds']} s" : null,
            ],
            'ivr' => [
                is_array($details['keys'] ?? null) ? '⌨ '.implode(' ', array_map(fn ($k) => strtok((string) $k, ' '), $details['keys'])) : null,
                isset($details['timeout_seconds']) ? "{$details['timeout_seconds']} s" : null,
            ],
            'extension' => [$details['current_profile'] ?? null],
            'call_flow_app' => [! empty($details['external_calls']) ? 'API: '.implode(', ', array_slice($details['external_calls'], 0, 2)) : null],
            default => [],
        };

        return self::text(implode(' · ', array_filter($parts)), 80);
    }

    /**
     * Route condition, Call Flow App chains shortened to their last steps.
     */
    private static function condition(string $condition): string
    {
        // "condition Test_horaire_secours_0: ((DateTime.Now.DayOfWeek == …" → its name in the Call Flow.
        $condition = preg_replace('/condition ([\w\x{00C0}-\x{017F}]+): [^›]*/u', '$1 ', $condition);
        $steps = array_values(array_filter(array_map('trim', explode('›', $condition)), fn ($s) => $s !== '' && $s !== 'always'));
        if ($steps === []) {
            return $condition === 'always' ? '' : self::text($condition, 70);
        }
        $condition = implode(' › ', $steps);
        $steps = array_map('trim', explode('›', $condition));
        if (count($steps) > 2) {
            $condition = '… › '.implode(' › ', array_slice($steps, -2));
        }

        return self::text($condition, 70);
    }

    private static function text(string $value, int $max = 60): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value));
        if (mb_strlen($value) > $max) {
            $value = mb_substr($value, 0, $max - 1).'…';
        }

        return str_replace(['"', '<', '>', '`', '|', '#'], ['\'', '‹', '›', '\'', '/', '№'], $value);
    }
}
