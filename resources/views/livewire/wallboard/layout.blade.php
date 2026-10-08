{{--
    Document shell for the public wallboard (see the #[Layout] docblock on
    PublicWallboard). The component itself renders a single root <div> -
    Livewire morphs that root on every wire:poll tick, so the <html>/<head>
    must live here rather than in the component view.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Wallboard' }}</title>
    <style>
        .wb-page {
            --page-bg: #f1f5f9;
            --heading: #0f172a;
            --subtle: #64748b;
            --card-border: #e2e8f0;
            --card-bg: #ffffff;
            --label: #64748b;
            --value: #0f172a;
            --badge-bg: rgba(15, 23, 42, .05);
            --badge-text: #475569;
            --badge-ring: rgba(15, 23, 42, .1);
        }

        .wb-page.level-green {
            --page-bg: #d1fae5;
            --heading: #022c22;
            --subtle: rgba(6, 78, 59, .7);
            --card-border: rgba(16, 185, 129, .4);
            --card-bg: #6ee7b7;
            --label: #065f46;
            --value: #022c22;
            --badge-bg: rgba(2, 44, 34, .1);
            --badge-text: #064e3b;
            --badge-ring: rgba(2, 44, 34, .2);
        }

        .wb-page.level-orange {
            --page-bg: #fef3c7;
            --heading: #451a03;
            --subtle: rgba(120, 53, 15, .7);
            --card-border: rgba(245, 158, 11, .4);
            --card-bg: #fcd34d;
            --label: #92400e;
            --value: #451a03;
            --badge-bg: rgba(69, 26, 3, .1);
            --badge-text: #78350f;
            --badge-ring: rgba(69, 26, 3, .2);
        }

        .wb-page.level-red {
            --page-bg: #fecaca;
            --heading: #450a0a;
            --subtle: rgba(127, 29, 29, .7);
            --card-border: rgba(239, 68, 68, .5);
            --card-bg: #f87171;
            --label: #7f1d1d;
            --value: #450a0a;
            --badge-bg: rgba(69, 10, 10, .1);
            --badge-text: #7f1d1d;
            --badge-ring: rgba(69, 10, 10, .2);
        }

        html, body {
            height: 100%;
            margin: 0;
        }

        .wb-page {
            min-height: 100%;
            background: var(--page-bg);
            color: var(--heading);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            padding: 1.5rem;
            box-sizing: border-box;
        }

        .wb-wrap {
            max-width: 1600px;
            margin: 0 auto;
        }

        .wb-topbar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .wb-heading {
            font-size: 2rem;
            font-weight: 700;
            color: var(--heading);
            margin: 0;
        }

        .wb-desc {
            font-size: 1.05rem;
            color: var(--subtle);
            margin: .25rem 0 0;
        }

        .wb-status {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .875rem;
            color: var(--subtle);
        }

        .wb-dot {
            display: inline-block;
            width: .625rem;
            height: .625rem;
            border-radius: 999px;
            background: #10b981;
        }

        .wb-dot.is-error {
            background: #f43f5e;
        }

        .wb-dot.is-live {
            animation: wb-pulse 1.6s ease-in-out infinite;
        }

        @keyframes wb-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .45; }
        }

        .wb-grid {
            display: grid;
            gap: 1rem;
        }

        .wb-tile {
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 4px 14px rgba(15, 23, 42, .08);
            min-height: 9rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .wb-tile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }

        .wb-tile-label {
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--label);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .wb-tile-badge {
            flex-shrink: 0;
            border-radius: .5rem;
            padding: .25rem .5rem;
            font-size: .95rem;
            font-weight: 600;
            background: var(--badge-bg);
            color: var(--badge-text);
            box-shadow: inset 0 0 0 1px var(--badge-ring);
        }

        .wb-tile-value {
            margin-top: .5rem;
            font-size: 3.25rem;
            font-weight: 800;
            color: var(--value);
            font-variant-numeric: tabular-nums;
        }

        .wb-tile-value.accent-amber { color: #d97706; }
        .wb-tile-value.accent-rose { color: #e11d48; }
        .wb-tile-value.accent-emerald { color: #059669; }
        .wb-page.level-green .wb-tile-value.accent-amber,
        .wb-page.level-green .wb-tile-value.accent-rose,
        .wb-page.level-green .wb-tile-value.accent-emerald,
        .wb-page.level-orange .wb-tile-value.accent-amber,
        .wb-page.level-orange .wb-tile-value.accent-rose,
        .wb-page.level-orange .wb-tile-value.accent-emerald,
        .wb-page.level-red .wb-tile-value.accent-amber,
        .wb-page.level-red .wb-tile-value.accent-rose,
        .wb-page.level-red .wb-tile-value.accent-emerald {
            color: var(--value);
        }

        .wb-message {
            margin-top: 6rem;
            text-align: center;
            color: var(--subtle);
            font-size: 1.1rem;
        }

        @media (max-width: 640px) {
            .wb-page { padding: 1rem; }
            .wb-heading { font-size: 1.5rem; }
            .wb-tile-value { font-size: 2.25rem; }
        }
    </style>
</head>

<body>
    {{ $slot }}
</body>

</html>
