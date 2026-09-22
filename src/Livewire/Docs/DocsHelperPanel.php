<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Livewire\Docs;

use CXEngine\ExpertStatistics\Support\DocsCatalog;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Slide-over "Documentation" help panel, included once via
 * <x-expert-statistics::docs.helper-panel /> in the shared cluster layout
 * (and the two settings pages that bypass it) so it's available on every
 * Expert Statistics page. Renders the Markdown guide shipped in
 * resources/docs/user/*.md (catalog: CXEngine\ExpertStatistics\Support\DocsCatalog)
 * client-side into a searchable, section-linked reading panel - no database,
 * no separate doc-hosting page.
 *
 * Deliberately unrelated to Livewire\Ai\AiChat / AiFloatingChat, which
 * answer questions about the user's call *data*, not about how to use the
 * UI. See resources/docs/ai-helper-architecture-plan.md for the (not yet
 * built) AI-powered version of this panel.
 */
class DocsHelperPanel extends Component
{
    public bool $open = false;

    public ?string $activeSectionId = null;

    public string $search = '';

    public function mount(): void
    {
        $this->activeSectionId = DocsCatalog::sectionIdForRoute(Route::currentRouteName())
            ?? DocsCatalog::sections()[0]['id'];
    }

    public function openPanel(): void
    {
        $this->open = true;
    }

    public function closePanel(): void
    {
        $this->open = false;
        $this->search = '';
    }

    public function select(string $sectionId): void
    {
        if (DocsCatalog::find($sectionId) !== null) {
            $this->activeSectionId = $sectionId;
            $this->search = '';
        }
    }

    /**
     * @return array<int, array{group: string, sections: array<int, array{id: string, title: string, file: string, routes: array<int, string>}>}>
     */
    #[Computed]
    public function catalog(): array
    {
        return DocsCatalog::catalog();
    }

    #[Computed]
    public function activeSection(): ?array
    {
        return $this->activeSectionId ? DocsCatalog::find($this->activeSectionId) : null;
    }

    #[Computed]
    public function activeContent(): string
    {
        $section = $this->activeSection;

        if ($section === null) {
            return '';
        }

        return $this->renderMarkdown($this->readSectionMarkdown($section['file']));
    }

    /**
     * @return array<int, array{id: string, title: string, group: string}>
     */
    #[Computed]
    public function searchResults(): array
    {
        $term = trim($this->search);

        if ($term === '') {
            return [];
        }

        $results = [];

        foreach ($this->catalog as $group) {
            foreach ($group['sections'] as $section) {
                $haystack = $section['title'].' '.$this->readSectionMarkdown($section['file']);

                if (Str::contains($haystack, $term, ignoreCase: true)) {
                    $results[] = [
                        'id' => $section['id'],
                        'title' => $section['title'],
                        'group' => $group['group'],
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * file.md => section id, so the Blade view's Alpine click handler can
     * turn a clicked in-content Markdown link (e.g. "02-permissions.md#foo")
     * into a select() call instead of a dead navigation.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function sectionIdsByFile(): array
    {
        return collect(DocsCatalog::sections())->mapWithKeys(
            fn (array $section) => [$section['file'] => $section['id']]
        )->all();
    }

    private function renderMarkdown(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                // IDs on every heading (for in-panel anchor scrolling from
                // cross-doc links), no inserted "#" permalink symbol.
                'apply_id_to_heading' => true,
                'insert' => 'none',
            ],
        ], [
            new HeadingPermalinkExtension,
        ]);
    }

    private function readSectionMarkdown(string $file): string
    {
        $path = __DIR__.'/../../../resources/docs/user/'.$file;

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    public function render()
    {
        return view('expert-statistics::livewire.docs.helper-panel');
    }
}
